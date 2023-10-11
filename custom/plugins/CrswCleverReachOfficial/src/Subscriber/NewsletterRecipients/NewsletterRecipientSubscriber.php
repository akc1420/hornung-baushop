<?php

namespace Crsw\CleverReachOfficial\Subscriber\NewsletterRecipients;

use Crsw\CleverReachOfficial\Components\EventHandlers\RecipientHandler;
use Crsw\CleverReachOfficial\Components\EventHandlers\TagHandler;
use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Special\Subscriber;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag;
use Crsw\CleverReachOfficial\Entity\Customer\Repositories\SubscriberRepository;
use Crsw\CleverReachOfficial\Entity\Tag\Repositories\TagRepository;
use Crsw\CleverReachOfficial\Service\BusinessLogic\SalesChannel\SalesChannelContextService;
use Shopware\Core\Content\Newsletter\Event\NewsletterConfirmEvent;
use Shopware\Core\Content\Newsletter\NewsletterEvents;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class NewsletterRecipientSubscriber
 *
 * @package Crsw\CleverReachOfficial\Subscriber\NewsletterRecipients
 */
class NewsletterRecipientSubscriber implements EventSubscriberInterface
{
    /**
     * Emails stored before newsletter recipient is deleted/changed
     *
     * @var array
     */
    private static $previousEmails = [];

    /**
     * Emails that have been changed on newsletter recipient update.
     *
     * @var array
     */
    private static $newEmails = [];
    /**
     * Emails of customer that unsubscribed.
     *
     * @var array
     */
    private static $deactivatedEmails = [];
    /**
     * @var RecipientHandler
     */
    private $recipientHandler;
    /**
     * @var SubscriberRepository
     */
    private $subscriberRepository;
    /**
     * @var TagHandler
     */
    private $tagHandler;
    /**
     * @var TagRepository
     */
    private $tagRepository;
    /**
     * @var SalesChannelContextService
     */
    private $salesChannelContextService;

    /**
     * NewsletterRecipientSubscriber constructor.
     *
     * @param RecipientHandler $recipientHandler
     * @param SubscriberRepository $subscriberRepository
     * @param Initializer $initializer
     * @param TagHandler $tagHandler
     * @param TagRepository $tagRepository
     * @param SalesChannelContextService $salesChannelContextService
     */
    public function __construct(
        RecipientHandler $recipientHandler,
        SubscriberRepository $subscriberRepository,
        Initializer $initializer,
        TagHandler $tagHandler,
        TagRepository $tagRepository,
        SalesChannelContextService $salesChannelContextService
    ) {
        Bootstrap::register();
        $initializer->registerServices();
        $this->recipientHandler = $recipientHandler;
        $this->subscriberRepository = $subscriberRepository;
        $this->tagHandler = $tagHandler;
        $this->tagRepository = $tagRepository;
        $this->salesChannelContextService = $salesChannelContextService;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            NewsletterEvents::NEWSLETTER_CONFIRM_EVENT => 'onNewsletterConfirm',
            NewsletterEvents::NEWSLETTER_RECIPIENT_WRITTEN_EVENT => 'onNewsletterSave',
            NewsletterEvents::NEWSLETTER_RECIPIENT_DELETED_EVENT => 'onNewsletterDelete',
            KernelEvents::CONTROLLER => 'saveDataForDelete',
            'newsletter_recipient_tag.deleted' => 'onNewsletterTagDelete',
        ];
    }

    /**
     * Newsletter confirmation by email.
     *
     * @param NewsletterConfirmEvent $event
     */
    public function onNewsletterConfirm(NewsletterConfirmEvent $event): void
    {
        if (!$this->recipientHandler->canHandle()) {
            return;
        }

        $recipient = $event->getNewsletterRecipient();

        $this->recipientHandler->recipientSubscribedEvent($recipient->getEmail());
    }

    /**
     * Newsletter recipient updated by administrator.
     *
     * @param EntityWrittenEvent $event
     */
    public function onNewsletterSave(EntityWrittenEvent $event): void
    {
        if (!$this->recipientHandler->canHandle()) {
            return;
        }

        $writeResults = $event->getWriteResults();
        $this->handleSubscriberChangeEvent($writeResults, $event);

        $this->tagHandler->tagCreated();
    }

    /**
     * Newsletter deleted by administrator.
     *
     * @param EntityDeletedEvent $event
     */
    public function onNewsletterDelete(EntityDeletedEvent $event): void
    {
        if (!$this->recipientHandler->canHandle()) {
            return;
        }

        $ids = $event->getIds();
        $subscribers = $this->subscriberRepository->getByIds($ids, $event->getContext());
        static::$previousEmails = array_merge(static::$previousEmails, static::$deactivatedEmails);

        foreach ($subscribers as $subscriber) {
            static::$previousEmails[] = $subscriber->getEmail();
        }

        foreach (static::$previousEmails as $email) {
            $this->recipientHandler->resyncRecipient([$email], [new Subscriber('Shopware 6')]);
            $this->recipientHandler->recipientDeletedEvent($email);
        }

        static::$previousEmails = [];
    }

    /**
     * @param ControllerEvent $event
     */
    public function saveDataForDelete(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->get('_route') === 'frontend.account.newsletter' &&
            !$request->get('option', false)) {
            $this->savePreviousEmailFromContext($request);
            return;
        }

        if (in_array(
            $request->get('_route'),
            ['api.newsletter_recipient.delete', 'api.newsletter_recipient.update'],
            true
        )) {
            $path = $request->get('path');
            // check if route contains subpaths
            if (!strpos($path, '/')) {
                $this->saveEmailForDelete(
                    $path,
                    $event->getRequest()->get('sw-context') ?: Context::createDefaultContext()
                );
            }
        }
    }

    /**
     * Newsletter tag deleted.
     *
     * @param EntityDeletedEvent $event
     */
    public function onNewsletterTagDelete(EntityDeletedEvent $event): void
    {
        $newsletterIds = [];
        $payloads = $event->getPayloads();
        $tagsForDelete = [];

        foreach ($payloads as $payload) {
            if (array_key_exists('newsletterRecipientId', $payload)) {
                $newsletterIds[] = $payload['newsletterRecipientId'];
                $tagsForDelete[$payload['newsletterRecipientId']] = array_key_exists('tagId', $payload)
                    ? $payload['tagId'] : '';
            }
        }

        $this->updateRecipients($newsletterIds, $event->getContext(), $tagsForDelete);
        $this->tagHandler->resyncSegments();
    }

    /**
     * Check if email changed and deactivates recipients with old email address
     *
     * @param array $sourceIds
     */
    private function deactivateOldEmails(array $sourceIds): void
    {
        $emailsForDeactivation = static::$deactivatedEmails;

        foreach ($sourceIds as $id) {
            if ($this->isEmailChanged($id)) {
                $emailsForDeactivation[] = static::$previousEmails[$id];
            }
        }

        foreach ($emailsForDeactivation as $email) {
            $this->recipientHandler->recipientDeletedEvent($email);
        }
    }

    /**
     * Check if newsletter recipient email changed
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
     * @param array $sourceIds
     * @param Context $context
     * @param array|null $tagIds
     */
    private function updateRecipients(array $sourceIds, Context $context, ?array $tagIds): void
    {
        $emailsForCreate = [];
        $newsletterRecipients = $this->subscriberRepository->getByIds($sourceIds, $context);

        foreach ($newsletterRecipients as $entity) {
            if ($entity->getStatus() === 'optOut') {
                $this->recipientHandler->recipientUnsubscribedEvent($entity->getEmail());
                continue;
            }

            if ($this->isEmailChanged($entity->getId())) {
                $this->recipientHandler->recipientUnsubscribedEvent(self::$previousEmails[$entity->getId()]);
                $emailsForCreate[$entity->getId()] = self::$newEmails[$entity->getId()];
                unset(
                    self::$newEmails[$entity->getId()],
                    self::$previousEmails[$entity->getId()]
                );
            } else {
                $emailsForCreate[$entity->getId()] = $entity->getEmail();
            }
        }

        foreach ($emailsForCreate as $key => $email) {
            $crTag = '';

            if (!empty($tagIds[$key])) {
                $tag = $this->tagRepository->getTagById($tagIds[$key], $context);
                $crTag = new Tag('Shopware 6', $tag->getName());
                $crTag->setType('Tag');
            }

            $this->recipientHandler->resyncRecipient([$email], $crTag ? [$crTag] : []);
        }
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
                $id = $this->subscriberRepository->getByEmail($customer->getEmail())['subscriberId'];
                static::$deactivatedEmails[bin2hex($id)] = $customer->getEmail();
            }
        }
    }

    /**
     * @param string|null $id
     * @param Context $context
     */
    private function saveEmailForDelete(?string $id, Context $context): void
    {
        if (!$id) {
            return;
        }

        $newsletterRecipient = $this->subscriberRepository->getByIds([$id], $context)->first();

        if ($newsletterRecipient) {
            static::$previousEmails[$id] = $newsletterRecipient->getEmail();
        }
    }

    /**
     * @param array $writeResults
     * @param EntityWrittenEvent $event
     */
    private function handleSubscriberChangeEvent(array $writeResults, EntityWrittenEvent $event): void
    {
        $emailsForCreate = [];
        foreach ($writeResults as $writeResult) {
            $payload = $writeResult->getPayload();
            if (!array_key_exists('id', $payload)) {
                continue;
            }

            $id = $payload['id'];
            if (empty($payload['email'])) {
                continue;
            }

            $email = $payload['email'];
            self::$newEmails[$id] = $email;
            if (array_key_exists('status', $payload) && $payload['status'] === 'optOut') {
                $this->recipientHandler->recipientUnsubscribedEvent($email);
                continue;
            }

            if ($this->isEmailChanged($id)) {
                $this->recipientHandler->recipientUnsubscribedEvent(self::$previousEmails[$id]);
                $emailsForCreate[$id] = self::$newEmails[$id];
                unset(
                    self::$newEmails[$id],
                    self::$previousEmails[$id]
                );
            } else {
                $emailsForCreate[$id] = $email;
            }
        }

        $sourceIds = $event->getIds();
        $this->deactivateOldEmails($sourceIds);

        if (!$emailsForCreate) {
            $this->updateRecipients($sourceIds, $event->getContext(), null);
        }

        foreach ($emailsForCreate as $email) {
            $this->recipientHandler->resyncRecipient([$email]);
        }
    }
}
