<?php /** @noinspection PhpUndefinedClassInspection */


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Automation;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO\AbandonedCart;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO\CartItem;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO\Trigger;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\AutomationRecordService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\Required\CartAutomationTriggerService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Entity\Currency\Repositories\CurrencyRepository;
use Crsw\CleverReachOfficial\Entity\Customer\Repositories\CustomerRepository;
use Crsw\CleverReachOfficial\Entity\Product\Repositories\ProductRepository;
use Crsw\CleverReachOfficial\Entity\SalesChannel\Repositories\SalesChannelRepository;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\Entities\RecoveryRecord;
use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use function unserialize;

/**
 * Class TriggerService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Automation
 */
class TriggerService implements CartAutomationTriggerService
{
    /**
     * @param mixed $cartId
     * @return Trigger|null
     */
    public function getTrigger($cartId): ?Trigger
    {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('c.*')->from('cart', 'c')->where("c.token =:cartId");
        $query->setParameter('cartId', $cartId);
        $cartData = $query->execute()->fetch();
        $cart = null;

        if (isset($cartData['cart'])) {
            /** @noinspection UnserializeExploitsInspection */
            $cart = unserialize((string)$cartData['cart']);
        }

        if (!$cart && isset($cartData['payload'])) {
            /** @noinspection UnserializeExploitsInspection */
            $cart = unserialize((string)$cartData['payload']);
        }

        $salesChannelId = $cartData['sales_channel_id'];

        $salesChannel = $this->getSalesChannelRepository()
            ->getSalesChannelById(bin2hex($salesChannelId), Context::createDefaultContext());

        if (!$cart instanceof Cart) {
            return null;
        }

        $customer = $this->getCustomer($cartData, $cartId);

        if (!$customer) {
            return null;
        }

        try {
            return $this->formatTrigger($cart, $customer, $cartData, $salesChannel);
        } catch (BaseException $e) {
            Logger::logError($e->getMessage());
            return null;
        }
    }

    /**
     * @param array $cartData
     * @param string $cartId
     *
     * @return CustomerEntity|null
     */
    private function getCustomer(array $cartData, string $cartId): ?CustomerEntity
    {
        if (!$cartData['customer_id']) {
            $record = $this->getAutomationRecordService()->findBy(['cartId' => $cartId])[0];
            return  $this->getCustomerRepository()->getCustomerByEmail(
                $record->getEmail(),
                Context::createDefaultContext()
            );
        }

        return $this->getCustomerRepository()->getCustomerById(
            bin2hex($cartData['customer_id']),
            Context::createDefaultContext()
        );
    }

    /**
     * @param Cart $cart
     * @param CustomerEntity $customer
     * @param array $cartData
     * @param SalesChannelEntity|null $salesChannel
     *
     * @return Trigger|null
     *
     * @throws RepositoryNotRegisteredException
     */
    private function formatTrigger(
        Cart $cart,
        CustomerEntity $customer,
        array $cartData,
        SalesChannelEntity $salesChannel = null
    ): ?Trigger {
        $trigger = new Trigger();
        $trigger->setPoolId($customer->getEmail());
        $trigger->setGroupId($this->getGroupService()->getId());
        $trigger->setCart($this->formatAbandonedCart($cart, $cartData, $customer->getEmail(), $salesChannel));

        return $trigger;
    }

    /**
     * @param Cart $cart
     * @param array $cartData
     * @param string $customerEmail
     * @param SalesChannelEntity|null $entity
     * @return AbandonedCart
     *
     * @throws RepositoryNotRegisteredException
     */
    private function formatAbandonedCart(
        Cart $cart,
        array $cartData,
        string $customerEmail,
        SalesChannelEntity $entity = null
    ): AbandonedCart {
        $abandonedCart = new AbandonedCart();
        $abandonedCart->setStoreId(AutomationService::STORE_ID_PREFIX . bin2hex($cartData['sales_channel_id']));
        $abandonedCart->setCurrency($this->getCurrency($cartData));
        $abandonedCart->setReturnUrl($this->getReturnUrl($customerEmail, $cart, $entity));

        $cartItems = $this->formatCartItems($cart, $entity);

        $abandonedCart->setTotal(
            array_reduce(
                $cart->getLineItems()->getElements(),
                static function ($cary, LineItem $item) {
                    return $cary + $item->getPrice()->getTotalPrice() * $item->getQuantity();
                },
                0
            )
        );
        $abandonedCart->setVat(
            array_reduce(
                $cart->getLineItems()->getElements(),
                static function ($cary, LineItem $item) {
                    $taxes = $item->getPrice()->getCalculatedTaxes()->getElements();
                    $sum = array_reduce($taxes, static function ($cary, $item) {
                        return $cary + $item->getTax();
                    }, 0);
                    return $cary + $sum;
                },
                0
            )
        );
        $abandonedCart->setCartItems($cartItems);
        $this->prepareCartRecovery($customerEmail, $cart);

        return $abandonedCart;
    }

    /**
     * @param string $email
     * @param Cart $cart
     *
     * @throws RepositoryNotRegisteredException
     */
    private function prepareCartRecovery(string $email, Cart $cart): void
    {
        $record = new RecoveryRecord();
        $record->setEmail($email);
        $record->setToken(hash('md5', time() . $cart->getToken()));
        $items = [];

        foreach ($cart->getLineItems()->getElements() as $element) {
            $items[$element->getReferencedId()] = $element->getQuantity();
        }

        $record->setItems($items);

        $this->getRecoveryRecordService()->create($record);
    }

    /**
     * @param array $cartData
     * @return string
     */
    private function getCurrency(array $cartData): string
    {
        $currency =  $this->getCurrencyRepository()
            ->getCurrencyById(bin2hex($cartData['currency_id']), Context::createDefaultContext());

        return $currency ? $currency->getIsoCode() : '';
    }

    /**
     * @param Cart $cart
     * @param SalesChannelEntity|null $entity
     *
     * @return CartItem[]
     */
    private function formatCartItems(Cart $cart, SalesChannelEntity $entity = null): array
    {
        $lineItems = $cart->getLineItems()->getElements();
        $cartItems = [];

        foreach ($lineItems as $item) {
            if ($item->getType() != LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }
            $cartItem = new CartItem();
            $cartItem->setId($item->getReferencedId());
            $cartItem->setName($item->getLabel());
            $cartItem->setSinglePrice($item->getPrice() ? $item->getPrice()->getTotalPrice() : '');
            $cartItem->setAmount($item->getQuantity());
            $cartItem->setSku($item->getPayload()['productNumber']);
            $cartItem->setDescription($this->getProductDescription($item));
            $cartItem->setImage($item->getCover() ? $item->getCover()->getUrl() : '');
            $cartItem->setProductUrl($this->getProductUrl($item, $entity));

            $cartItems[] = $cartItem;
        }

        return $cartItems;
    }

    /**
     * @param LineItem $item
     *
     * @return string
     */
    private function getProductDescription(LineItem $item): string
    {
        $product = $this->getProductRepository()
            ->getProductById($item->getReferencedId(), Context::createDefaultContext());

        return ($product && $product->getDescription() !== null) ? $product->getDescription() : '';
    }

    /**
     * @param LineItem $item
     * @param SalesChannelEntity|null $entity
     *
     * @return string
     */
    private function getProductUrl(LineItem $item, SalesChannelEntity $entity = null): string
    {
        $urlGenerator = $this->getUrlGenerator();

        if ($entity && $entity->getDomains()) {
            $url = $entity->getDomains()->first()->getUrl();
            $host = str_replace(['http://', 'https://'], '', $url);
            $urlGenerator->getContext()->setHost($host);
        }

        return $urlGenerator->generate(
            'frontend.detail.page',
            ['productId' => $item->getReferencedId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @param string $email
     * @param Cart $cart
     * @param SalesChannelEntity|null $entity
     * @return string
     */
    private function getReturnUrl(string $email, Cart $cart, SalesChannelEntity $entity = null): string
    {
        $token = hash('md5', time() . $cart->getToken());

        $urlGenerator = $this->getUrlGenerator();

        if ($entity && $entity->getDomains()) {
            $url = $entity->getDomains()->first()->getUrl();
            $host = str_replace(['http://', 'https://'], '', $url);
            $urlGenerator->getContext()->setHost($host);
        }

        return $urlGenerator->generate(
            'frontend.checkout.confirm.page',
            [
                'userEmail' => $email,
                'token' => $token,
                'recover' => true
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @return UrlGeneratorInterface
     */
    private function getUrlGenerator(): UrlGeneratorInterface
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(UrlGeneratorInterface::class);
    }

    /**
     * @return GroupService
     */
    private function getGroupService(): GroupService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(GroupService::class);
    }
    /**
     * @return ProductRepository
     */
    private function getProductRepository(): ProductRepository
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(ProductRepository::class);
    }

    /**
     * @return CurrencyRepository
     */
    private function getCurrencyRepository(): CurrencyRepository
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(CurrencyRepository::class);
    }

    /**
     * @return CustomerRepository
     */
    private function getCustomerRepository(): CustomerRepository
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(CustomerRepository::class);
    }

    /**
     * @return Connection
     */
    private function getConnection(): Connection
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Connection::class);
    }

    /**
     * @return RecoveryRecordService
     */
    private function getRecoveryRecordService(): RecoveryRecordService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(RecoveryRecordService::class);
    }

    /**
     * @return AutomationRecordService
     */
    private function getAutomationRecordService(): AutomationRecordService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(AutomationRecordService::class);
    }

    /**
     * @return SalesChannelRepository
     */
    private function getSalesChannelRepository(): SalesChannelRepository
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(SalesChannelRepository::class);
    }
}
