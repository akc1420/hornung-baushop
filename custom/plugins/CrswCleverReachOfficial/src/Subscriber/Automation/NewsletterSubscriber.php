<?php /** @noinspection PhpUndefinedClassInspection */


namespace Crsw\CleverReachOfficial\Subscriber\Automation;

use Crsw\CleverReachOfficial\Components\EventHandlers\DoubleOptInHandler;
use Crsw\CleverReachOfficial\Components\EventHandlers\RecipientHandler;
use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\DTO\DoiData;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToRetriveFormException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Crsw\CleverReachOfficial\Entity\Customer\Repositories\SubscriberRepository;
use Crsw\CleverReachOfficial\Service\BusinessLogic\DoubleOptIn\DoubleOptInRecordService;
use DateTime;
use Shopware\Core\Checkout\Customer\Event\CustomerRegisterEvent;
use Shopware\Core\Checkout\Customer\Event\GuestCustomerRegisterEvent;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class NewsletterSubscriber
 *
 * @package Crsw\CleverReachOfficial\Subscriber\Automation
 */
class NewsletterSubscriber implements EventSubscriberInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var SubscriberRepository
     */
    private $subscriberRepository;
    /**
     * @var DoubleOptInRecordService
     */
    private $doiRecordService;
    /**
     * @var DoubleOptInHandler
     */
    private $handler;
    /**
     * @var RecipientHandler
     */
    private $recipientHandler;

    /**
     * NewsletterSubscriber constructor.
     *
     * @param Initializer $initializer
     * @param RequestStack $requestStack
     * @param SubscriberRepository $subscriberRepository
     * @param DoubleOptInRecordService $doiRecordService
     * @param DoubleOptInHandler $handler
     * @param RecipientHandler $recipientHandler
     */
    public function __construct(
        Initializer $initializer,
        RequestStack $requestStack,
        SubscriberRepository $subscriberRepository,
        DoubleOptInRecordService $doiRecordService,
        DoubleOptInHandler $handler,
        RecipientHandler $recipientHandler
    ) {
        Bootstrap::register();
        $initializer->registerServices();
        $this->requestStack = $requestStack;
        $this->subscriberRepository = $subscriberRepository;
        $this->doiRecordService = $doiRecordService;
        $this->handler = $handler;
        $this->recipientHandler = $recipientHandler;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CustomerRegisterEvent::class => 'onSubscribe',
            GuestCustomerRegisterEvent::class => 'onSubscribe'
        ];
    }

    /**
     * Handles newsletter subscription.
     *
     * @param CustomerRegisterEvent $event
     */
    public function onSubscribe(CustomerRegisterEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return;
        }

        $customer = $event->getCustomer();
        $newsletter = $request->get('option');

        $subscriber = $this->subscriberRepository->getByEmail($customer->getEmail());

        if ($newsletter !== 'direct' || $subscriber) {
            return;
        }

        if ($this->isCleverReachDoiEnabled($event)) {
            try {
                $this->sendCleverReachDoi($customer->getEmail(), $event->getSalesChannelId());
            } catch (BaseException $e) {
                Logger::logError('Failed to send double opt in email because: ' . $e->getMessage(), 'Integration');
            }

            return;
        }

        $this->createSubscriber($customer->getEmail(), $event->getSalesChannelContext()->getSalesChannel());
    }

    /**
     * @param CustomerRegisterEvent $event
     *
     * @return bool
     */
    private function isCleverReachDoiEnabled(CustomerRegisterEvent $event): bool
    {
        $salesChannelId = $event->getSalesChannelId();
        try {
            $doiRecord = $this->doiRecordService->findBySalesChannelId($salesChannelId);

            return $doiRecord && $doiRecord->isStatus();
        } catch (BaseException $e) {
            return false;
        }
    }

    /**
     * @param string $email
     * @param string $salesChannelId
     *
     * @throws FailedToRetriveFormException
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    private function sendCleverReachDoi(string $email, string $salesChannelId): void
    {
        $data = new DoiData($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_REFERER'], $_SERVER['HTTP_USER_AGENT']);
        $this->handler->sendDoubleOptInEmail($email, $data, $salesChannelId);
    }

    /**
     * @param string $email
     * @param SalesChannelEntity $salesChannelEntity
     */
    private function createSubscriber(string $email, SalesChannelEntity $salesChannelEntity): void
    {
        $receiver = new Receiver();
        $receiver->setEmail($email);
        $receiver->setRegistered(new DateTime());
        $receiver->setActivated(new DateTime());
        $receiver->setShop($salesChannelEntity->getName());

        $this->subscriberRepository->create($receiver);

        $this->recipientHandler->recipientSubscribedEvent($email);
    }
}
