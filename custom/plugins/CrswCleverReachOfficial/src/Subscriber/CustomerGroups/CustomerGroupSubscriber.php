<?php


namespace Crsw\CleverReachOfficial\Subscriber\CustomerGroups;


use Crsw\CleverReachOfficial\Components\EventHandlers\TagHandler;
use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Crsw\CleverReachOfficial\Entity\CustomerGroup\Repositories\CustomerGroupRepository;
use Shopware\Core\Checkout\Customer\CustomerEvents;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CustomerGroupSubscriber
 *
 * @package Crsw\CleverReachOfficial\Subscriber\CustomerGroups
 */
class CustomerGroupSubscriber implements EventSubscriberInterface
{
    /**
     * @var array
     */
    private static $groupsForDelete = [];
    /**
     * @var TagHandler
     */
    private $tagHandler;
    /**
     * @var CustomerGroupRepository
     */
    private $customerGroupRepository;

    /**
     * CustomerGroupSubscriber constructor.
     *
     * @param TagHandler $tagHandler
     * @param CustomerGroupRepository $customerGroupRepository
     * @param Initializer $initializer
     */
    public function __construct(
        TagHandler $tagHandler,
        CustomerGroupRepository $customerGroupRepository,
        Initializer $initializer
    ) {
        Bootstrap::register();
        $initializer->registerServices();

        $this->tagHandler = $tagHandler;
        $this->customerGroupRepository = $customerGroupRepository;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CustomerEvents::CUSTOMER_GROUP_WRITTEN_EVENT => 'onCustomerGroupChange',
            CustomerEvents::CUSTOMER_GROUP_DELETED_EVENT => 'onCustomerGroupDelete',
            KernelEvents::CONTROLLER => 'saveDataForDelete',
        ];
    }

    /**
     * Customer group created or modified.
     *
     * @param EntityWrittenEvent $event
     */
    public function onCustomerGroupChange(EntityWrittenEvent $event): void
    {
        if (!$this->tagHandler->canHandle()) {
            return;
        }

        $this->tagHandler->tagCreated();

        foreach ($event->getIds() as $id) {
            if (!empty(static::$groupsForDelete[$id])) {
                $this->tagHandler->deleteSegment(static::$groupsForDelete[$id]);
                unset(static::$groupsForDelete[$id]);
            }
        }
    }

    /**
     * Customer group deleted.
     *
     * @param EntityDeletedEvent $event
     */
    public function onCustomerGroupDelete(EntityDeletedEvent $event): void
    {
        if (!$this->tagHandler->canHandle()) {
            return;
        }

        $this->tagHandler->resyncSegments();
    }

    /**
     * @param ControllerEvent $event
     */
    public function saveDataForDelete(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $context = $request->get('sw-context');

        if ($request->get('_route') === 'api.customer_group.update') {
            $groupId = $request->get('path');
            // check if route contains subpaths
            if (!strpos($groupId, '/')) {
                $this->saveOldGroupName($groupId, $context ?:
                    Context::createDefaultContext());
            }
        }
    }

    private function saveOldGroupName(string $groupId, Context $context): void
    {
        $customerGroup = $this->customerGroupRepository->getCustomerGroupById($groupId, $context);

        if ($customerGroup) {
            static::$groupsForDelete[$customerGroup->getId()] = $customerGroup->getName();
        }
    }
}