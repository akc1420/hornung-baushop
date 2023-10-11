<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Order\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Order\Contracts\OrderService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\Attribute\Attribute;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\Category\Category;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Modifier\Value\Increment;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

class OrderItemsSyncTask extends Task
{
    /**
     * Integrated system order id.
     *
     * @var string | int
     */
    protected $orderId;
    /**
     * Buyer's email.
     *
     * @var string
     */
    protected $receiverEmail;
    /**
     * Order tracking mailing id.
     *
     * @var string
     */
    protected $mailingId;

    /**
     * OrderItemsSyncTask constructor.
     *
     * @param int|string $orderId
     * @param string $receiverEmail
     * @param string $mailingId
     */
    public function __construct($orderId, $receiverEmail, $mailingId = '')
    {
        $this->orderId = $orderId;
        $this->receiverEmail = $receiverEmail;
        $this->mailingId = $mailingId;
    }

    /**
     * Returns array representation of the Order Items Sync Task.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'orderId' => $this->orderId,
            'receiverEmail' => $this->receiverEmail,
            'mailingId' => $this->mailingId
        );
    }

    /**
     * Transforms array of data to the Order Items Sync Task.
     *
     * @param array $data
     *
     * @return static
     */
    public static function fromArray(array $data)
    {
        return new static(
            $data['orderId'],
            $data['receiverEmail'],
            $data['mailingId']
        );
    }

    /**
     * Serializes task.
     *
     * @return string
     */
    public function serialize()
    {
        return Serializer::serialize(
            array(
                $this->orderId,
                $this->receiverEmail,
                $this->mailingId,
            )
        );
    }

    /**
     * Unserializes serialized task.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list($this->orderId, $this->receiverEmail, $this->mailingId) = Serializer::unserialize($serialized);
    }

    /**
     * Appends order item information to a CleverReach receiver identified by the email.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function execute()
    {
        if (!$this->getOrderService()->canSynchronizeOrderItems()) {
            $this->reportProgress(100);

            return;
        }

        $groupId = $this->getGroupService()->getId();

        $receiver = new Receiver();
        $receiver->setEmail($this->receiverEmail);
        $receiver->setSource($this->getOrderService()->getOrderSource($this->orderId));

        $this->reportProgress(5);

        $orderItems = $this->getOrderService()->getOrderItems($this->orderId);

        $this->reportAlive();

        foreach ($orderItems as $orderItem) {
            if  ($this->mailingId !== '') {
                $orderItem->setMailingId($this->mailingId);
            }

            $receiver->addOrderItem($orderItem);

            $this->addAttributeTags($orderItem->getAttributes(), $receiver);
            $this->addCategoryTags($orderItem->getCategories(), $receiver);
        }

        $receiver->addModifier(new Increment('ordercount', 1));

        $this->reportProgress(70);

        $this->getReceiverProxy()->upsertPlus($groupId, array($receiver));

        $this->reportProgress(100);
    }

    /**
     * Retrieves group service.
     *
     * @return GroupService
     */
    protected function getGroupService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(GroupService::CLASS_NAME);
    }

    /**
     * Retrieves order service.
     *
     * @return OrderService
     */
    protected function getOrderService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(OrderService::CLASS_NAME);
    }

    /**
     * Retrieves receiver proxy.
     *
     * @return Proxy
     */
    protected function getReceiverProxy()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }

    /**
     * Adds product attributes as receiver tags (Source-AttributeKey.AttributeValue)
     * @param Attribute[] $attributes
     * @param Receiver $receiver
     */
    protected function addAttributeTags(array $attributes, Receiver $receiver)
    {
        foreach ($attributes as $attribute) {
            $receiver->addTag($this->createTag($attribute->getValue(), $attribute->getKey()));
        }
    }

    /**
     * Adds product categories as receiver tags (Source-Category.CategoryValue)
     * @param Category[] $categories
     * @param Receiver $receiver
     */
    private function addCategoryTags(array $categories, Receiver $receiver)
    {
        foreach ($categories as $category) {
            $receiver->addTag($this->createTag($category->getValue(), 'Category'));
        }
    }

    /**
     * Creates tag with given value and type.
     *
     * @param string $value
     * @param string $type
     *
     * @return Tag
     */
    protected function createTag($value, $type)
    {
        $source = $this->getConfigService()->getIntegrationName();
        $tag = new Tag($source, $value);
        $tag->setType($type);

        return $tag;
    }
}
