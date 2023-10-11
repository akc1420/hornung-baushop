<?php

namespace Crsw\CleverReachOfficial\Subscriber\Customers;

use Crsw\CleverReachOfficial\Components\EventHandlers\RecipientHandler;
use Crsw\CleverReachOfficial\Components\EventHandlers\TagHandler;
use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Special\Buyer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Special\Contact;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Entity\Customer\Repositories\CustomerRepository;
use Crsw\CleverReachOfficial\Entity\Customer\Repositories\SubscriberRepository;
use Crsw\CleverReachOfficial\Entity\CustomerGroup\Repositories\CustomerGroupRepository;
use Crsw\CleverReachOfficial\Entity\SalesChannel\Repositories\SalesChannelRepository;
use Crsw\CleverReachOfficial\Entity\Tag\Repositories\TagRepository;
use Crsw\CleverReachOfficial\Service\BusinessLogic\SalesChannel\SalesChannelContextService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Tag\TagService;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Checkout\Customer\CustomerEvents;
use Shopware\Core\Checkout\Customer\Event\CustomerRegisterEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\Tag\TagEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CustomerSubscriber
 *
 * @package Crsw\CleverReachOfficial\Subscriber\Customers
 */
class CustomerSubscriber implements EventSubscriberInterface
{
    /**
     * Emails stored before customer is deleted/changed.
     *
     * @var array
     */
    private static $previousEmails = [];
    /**
     * Emails that have been changed on customer update.
     *
     * @var array
     */
    private static $newEmails = [];
    /**
     * @var RecipientHandler
     */
    private $recipientHandler;
    /**
     * @var TagHandler
     */
    private $tagHandler;
    /**
     * @var CustomerRepository
     */
    private $customerRepository;
    /**
     * @var CustomerGroupRepository
     */
    private $customerGroupRepository;
    /**
     * @var SubscriberRepository
     */
    private $subscriberRepository;
    /**
     * @var TagRepository
     */
    private $tagRepository;
    /**
     * @var SalesChannelRepository
     */
    private $salesChannelRepository;
    /**
     * @var SalesChannelContextService
     */
    private $salesChannelContextService;

    /**
     * CustomerSubscriber constructor.
     *
     * @param RecipientHandler $recipientHandler
     * @param CustomerRepository $customerRepository
     * @param Initializer $initializer
     * @param CustomerGroupRepository $customerGroupRepository
     * @param SubscriberRepository $subscriberRepository
     * @param TagHandler $tagHandler
     * @param TagRepository $tagRepository
     * @param SalesChannelRepository $salesChannelRepository
     * @param SalesChannelContextService $salesChannelContextService
     */
    public function __construct(
        RecipientHandler $recipientHandler,
        CustomerRepository $customerRepository,
        Initializer $initializer,
        CustomerGroupRepository $customerGroupRepository,
        SubscriberRepository $subscriberRepository,
        TagHandler $tagHandler,
        TagRepository $tagRepository,
        SalesChannelRepository $salesChannelRepository,
        SalesChannelContextService $salesChannelContextService
    ) {
        Bootstrap::register();
        $initializer->registerServices();

        $this->recipientHandler = $recipientHandler;
        $this->customerRepository = $customerRepository;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->subscriberRepository = $subscriberRepository;
        $this->tagHandler = $tagHandler;
        $this->tagRepository = $tagRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->salesChannelContextService = $salesChannelContextService;
    }

    /**
     * Returns subscribed events.
     *
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CustomerEvents::CUSTOMER_REGISTER_EVENT => 'onCustomerRegister',
            CustomerRegisterEvent::class => 'onCustomerRegister',
            CustomerEvents::CUSTOMER_DELETED_EVENT => 'onCustomerDelete',
            CustomerEvents::CUSTOMER_WRITTEN_EVENT => 'onCustomerSave',
            CustomerEvents::CUSTOMER_ADDRESS_WRITTEN_EVENT => 'onCustomerAddressSave',
            'customer_tag.deleted' => 'onCustomerTagDelete',
            KernelEvents::CONTROLLER => 'saveDataForDelete',
        ];
    }

    /**
     * Customer registered account.
     *
     * @param CustomerRegisterEvent $event
     */
    public function onCustomerRegister(CustomerRegisterEvent $event): void
    {
        if (!$this->recipientHandler->canHandle()) {
            return;
        }

        $customer = $event->getCustomer();
        $this->recipientHandler->resyncRecipient([$customer->getEmail()]);
    }

    /**
     * Customer deleted.
     *
     * @param EntityDeletedEvent $event
     */
    public function onCustomerDelete(EntityDeletedEvent $event): void
    {
        if (!$this->recipientHandler->canHandle()) {
            return;
        }

        $this->syncPreviousEmails($event->getContext());

        static::$previousEmails = [];
    }

    /**
     * Customer created or modified.
     *
     * @param EntityWrittenEvent $event
     */
    public function onCustomerSave(EntityWrittenEvent $event): void
    {
        if (!$this->recipientHandler->canHandle()) {
            return;
        }

        $writeResults = $event->getWriteResults();

        foreach ($writeResults as $writeResult) {
            $payload = $writeResult->getPayload();
            if (array_key_exists('email', $payload) && array_key_exists('id', $payload)) {
                $id = $payload['id'];
                self::$newEmails[$id] = $payload['email'];

                if ($this->isEmailChanged($id)) {
                    $this->recipientHandler->recipientUnsubscribedEvent(self::$previousEmails[$id]);
                    unset(self::$previousEmails[$id]);
                }
            }
        }

        $sourceIds = $event->getIds();
        $this->syncPreviousEmails($event->getContext());
        $this->syncNewRecipients($sourceIds, $event->getContext());
        $this->tagHandler->tagCreated();

        static::$previousEmails = [];
        static::$newEmails = [];
    }

    /**
     * Customer address changed.
     *
     * @param EntityWrittenEvent $event
     */
    public function onCustomerAddressSave(EntityWrittenEvent $event): void
    {
        if (!$this->recipientHandler->canHandle()) {
            return;
        }

        $emails = $this->getCustomerEmails($event);

		if (empty($emails)) {
			return;
		}

	    $this->recipientHandler->resyncRecipient($emails);
    }

    /**
     * Customer tag deleted.
     *
     * @param EntityDeletedEvent $event
     */
    public function onCustomerTagDelete(EntityDeletedEvent $event): void
    {
        if (!$this->recipientHandler->canHandle()) {
            return;
        }

        $emails = $this->getCustomerEmails($event);

	    if (empty($emails)) {
		    return;
	    }

        $payloads = $event->getPayloads();
        $deletedTags = [];

        foreach ($payloads as $payload) {
            if (array_key_exists('tagId', $payload)) {
                $tag = $this->tagRepository->getTagById($payload['tagId'], $event->getContext());
                $crTag = new Tag('Shopware 6', $tag->getName());
                $crTag->setType('Tag');
                $deletedTags[] = $crTag;
            }
        }

        $this->recipientHandler->resyncRecipient($emails, $deletedTags);
        $this->tagHandler->resyncSegments();
    }

    /**
     * Saves data for delete.
     *
     * @param ControllerEvent $event
     */
    public function saveDataForDelete(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $routeName = $request->get('_route');

        if (!in_array(
            $routeName,
            ['api.customer.delete', 'api.customer.update', 'frontend.account.profile.email.save']
        )) {
            return;
        }

        if (!$this->recipientHandler->canHandle()) {
            return;
        }

        if (in_array($routeName, ['api.customer.delete', 'api.customer.update'])) {
            $path = $request->get('path');
            // check if route contains subpaths
            if (!strpos($path, '/')) {
                $this->savePreviousEmail(
                    $path,
                    $event->getRequest()->get('sw-context') ?: Context::createDefaultContext()
                );
            }
        } elseif ($routeName === 'frontend.account.profile.email.save') {
            $this->savePreviousEmailFromContext($request);
        }
    }


    private function syncPreviousEmails(Context $context): void
    {
        foreach (static::$previousEmails as $email) {
            if ($this->subscriberRepository->getByEmail($email)) {
                $this->removeOldTags($email, $context);
            } else {
                $this->recipientHandler->recipientDeletedEvent($email);
            }
        }
    }

    /**
     * @param string $email
     * @param Context $context
     */
    private function removeOldTags(string $email, Context $context): void
    {
        $customerGroups = $this->customerGroupRepository->getCustomerGroups($context);
        try {
            $customerTags = $this->tagRepository->getTags($context);
        } catch (DBALException $e) {
            Logger::logError('Failed to get tags because: ' . $e->getMessage());
            $customerTags = [];
        }
        $salesChannels = $this->salesChannelRepository->getSalesChannels($context);
        $tagsForDelete = [new Buyer('Shopware 6'), new Contact('Shopware 6')];

        /** @var CustomerGroupEntity $group */
        foreach ($customerGroups as $group) {
            $tag = new Tag('Shopware 6', trim($group->getTranslation('name')));
            $tag->setType(TagService::CUSTOMER_GROUP_TAG);
            $tagsForDelete[] = $tag;
        }

        /** @var TagEntity $customerTag */
        foreach ($customerTags as $customerTag) {
            $tag = new Tag('Shopware 6', trim($customerTag->getName()));
            $tag->setType(TagService::TAG);
            $tagsForDelete[] = $tag;
        }

        /** @var SalesChannelEntity $channel */
        foreach ($salesChannels as $channel) {
            $tag = new Tag('Shopware 6', trim($channel->getName()));
            $tag->setType(TagService::SHOP_TAG);
            $tagsForDelete[] = $tag;
        }

        $this->recipientHandler->resyncRecipient([$email], $tagsForDelete);
    }

    /**
     * @param Request $request
     */
    private function savePreviousEmailFromContext(Request $request): void
    {
        /** @var SalesChannelContext $salesChannelContext */
        $salesChannelContext = $request->get('sw-sales-channel-context') ?:
            $this->salesChannelContextService->getSalesChannelContext($request);

        if ($salesChannelContext) {
            $customer = $salesChannelContext->getCustomer();
            if ($customer) {
                static::$previousEmails[$customer->getId()] = $customer->getEmail();
            }
        }
    }

    /**
     * Saves previous email.
     *
     * @param string|null $id
     * @param Context $context
     */
    private function savePreviousEmail(?string $id, Context $context): void
    {
        if (!$id) {
            return;
        }

        $customer = $this->customerRepository->getCustomerById($id, $context);

        if ($customer) {
            static::$previousEmails[$id] = $customer->getEmail();
        }
    }

    /**
     *
     * @param array $sourceIds
     * @param Context $context
     *
     * @return void
     */
    private function syncNewRecipients(array $sourceIds, Context $context): void
    {
        $emailsAndGroupsForResync = $this->getEmailsAndTagsForResync($sourceIds, $context);

        $newsletterEmailsForSync = $emailsAndGroupsForResync['newsletterEmailsForSync'];
        $customerGroups = $emailsAndGroupsForResync['customerGroups'];

        foreach ($newsletterEmailsForSync as $id => $email) {
            $tags = !empty($customerGroups[$id]) ? [$customerGroups[$id]] : [];
            $this->recipientHandler->resyncRecipient([$email], $tags);
        }
    }

    /**
     * @param array $sourceIds
     * @param Context $context
     *
     * @return array
     *
     * @noinspection NullPointerExceptionInspection
     */
    private function getEmailsAndTagsForResync(array $sourceIds, Context $context): array
    {
        $newsletterEmailsForSync = [];
        $customerGroups = [];

        foreach ($sourceIds as $id) {
            $customer = $this->customerRepository->getCustomerById($id, $context);
            $newsletterEmailsForSync[$id] = !empty(static::$newEmails[$id]) ?
                static::$newEmails[$id] : $customer->getEmail();

            if ($customer->getGroup()) {
                $tag = new Tag(TagService::SOURCE, $customer->getGroup()->getName());
                $tag->setType(TagService::CUSTOMER_GROUP_TAG);
                $customerGroups[$id] = $tag;
            }
        }

        return [
            'newsletterEmailsForSync' => $newsletterEmailsForSync,
            'customerGroups' => $customerGroups
        ];
    }

    /**
     * Check if customer email changed
     *
     * @param string $id
     *
     * @return bool
     */
    private function isEmailChanged(string $id): bool
    {
        return !empty(self::$previousEmails)
            && !empty(self::$newEmails)
            && self::$previousEmails[$id] !== self::$newEmails[$id];
    }

    /**
     * @param $event
     * @return array
     */
    protected function getCustomerEmails($event): array
    {
        $customerIds = [];
        $writeResults = $event->getWriteResults();

        foreach ($writeResults as $result) {
            $payload = $result->getPayload();

            if (array_key_exists('customerId', $payload)) {
                $customerIds[] = $payload['customerId'];
            }
        }

        if (empty($customerIds)) {
            return [];
        }

        $customers = $this->customerRepository->getCustomersByIds($customerIds, $event->getContext());
        $emails = [];

        foreach ($customers as $customer) {
            $emails[] = $customer->getEmail();
        }

        return $emails;
    }
}
