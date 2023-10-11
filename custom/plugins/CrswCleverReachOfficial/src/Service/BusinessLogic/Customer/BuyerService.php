<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Customer;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Order\Contracts\OrderService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Modifier\Value\Decrement;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Special\Buyer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Special\Contact;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Entity\Customer\Repositories\BuyerRepository;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Configuration\ConfigService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Tag\TagService;
use DateTime;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class BuyerService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Customer
 */
class BuyerService extends BaseReceiverService
{
    /**
     * @var ParameterBagInterface
     */
    private $params;

    /**
     * BuyerService constructor.
     *
     * @param BuyerRepository $repository
     * @param ParameterBagInterface $params
     */
    public function __construct(BuyerRepository $repository, ParameterBagInterface $params)
    {
        $this->baseRepository = $repository;
        $this->params = $params;
    }

    /**
     * Sets receiver tags.
     *
     * @param Receiver $receiver
     */
    protected function setTags(Receiver $receiver): void
    {
        $receiver->addModifier(new Decrement('tags', (string)(new Contact('Shopware 6'))));
        $receiver->addTag(new Buyer('Shopware 6'));
    }

    /**
     * @param CustomerEntity $entity
     * @param bool $isServiceSpecificDataRequired
     *
     * @return Receiver
     *
     * @throws QueryFilterInvalidParamException
     */
    protected function formatReceiver($entity, bool $isServiceSpecificDataRequired): Receiver
    {
        $receiver = parent::formatReceiver($entity, $isServiceSpecificDataRequired);

        $receiver->setActivated($entity->getCreatedAt());
        $this->setAddressAndCompanyInfo($entity, $receiver);

        if ($entity->getBirthday()) {
            $receiver->setBirthday((new DateTime())->setTimestamp($entity->getBirthday()->getTimestamp()));
        }

        $address = $this->getAddress($entity);
        if ($address) {
            $receiver->setPhone($address->getPhoneNumber());
        }

        $receiver->setShop($entity->getSalesChannel()->getName());
        $receiver->setCustomerNumber($entity->getCustomerNumber());
        $receiver->setLanguage($entity->getLanguage()->getName());

        if ($entity->getGuest()) {
            $guestTag = new Tag('Shopware 6', 'Guest');
            $guestTag->setType(TagService::ORIGIN);
            $receiver->addTag($guestTag);
        }

        if ($entity->getGroup()) {
            $tag = new Tag('Shopware 6', $entity->getGroup()->getTranslation('name'));
            $tag->setType(self::GROUP);
            $receiver->addTag($tag);
        }

        $receiver->setLastOrderDate($this->getLastOrderDate($entity));
        $receiver->setOrderCount($this->countOrders($entity));

        $orderCustomers = $entity->getOrderCustomers();

        $totalSpent = 0;

        if ($orderCustomers) {
            foreach ($orderCustomers->getElements() as $item) {
                $totalSpent += $item->getOrder() ? $item->getOrder()->getPrice()->getTotalPrice() : 0;
            }
        }

        $receiver->setTotalSpent($totalSpent);

        if ($isServiceSpecificDataRequired && $receiver->getOrderCount() > 0) {
            $this->setOrderData($receiver, $this->getConfigService()->getAllOrdersSynced());
        }

        return $receiver;
    }

    private function setOrderData($receiver, $ordersOlderThanOneYear): void
    {
        $date = null;

        if ($ordersOlderThanOneYear) {
            $date = new DateTime();
            $date->modify('-1 years')->format('Y-m-d');
        }

        $orderItems = $this->getOrderService()->getOrderItemsByCustomerEmail(
            $receiver->getEmail(),
            $date
        );

        $receiver->setOrderItems($orderItems);

        foreach ($orderItems as $item) {
            foreach ($item->getCategories() as $category) {
                $tag = new Tag('Shopware 6', $category->getValue());
                $tag->setType('Category');
                $receiver->addTag($tag);
            }

            if ($item->getVendor()) {
                $tag = new Tag('Shopware 6', $item->getVendor());
                $tag->setType('Manufacturer');
                $receiver->addTag($tag);
            }
        }
    }

    /**
     * @param CustomerEntity $customer
     *
     * @return int
     */
    private function countOrders(CustomerEntity $customer): int
    {
        if (version_compare($this->params->get('kernel.shopware_version'), '6.4.3', 'ge')) {
            return $this->baseRepository->countOrders($customer->getEmail());
        }

        return $customer->getOrderCount();
    }

    /**
     * @param CustomerEntity $customer
     *
     * @return DateTime|null
     */
    private function getLastOrderDate(CustomerEntity $customer): ?DateTime
    {
        $lastOrderDate = $customer->getLastOrderDate();
        if (version_compare($this->params->get('kernel.shopware_version'), '6.4.3', 'ge')) {
            foreach ($customer->getOrderCustomers()->getOrders() as $order) {
                if (!$lastOrderDate || $order->getOrderDate() > $lastOrderDate) {
                    $lastOrderDate = $order->getOrderDate();
                }
            }
        }

        return $lastOrderDate ? (new DateTime())->setTimestamp($lastOrderDate->getTimestamp()) : null;
    }

    /**
     * @return OrderService
     */
    private function getOrderService(): OrderService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(OrderService::class);
    }

    /**
     * @return ConfigService
     */
    private function getConfigService(): ConfigService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::class);
    }
}