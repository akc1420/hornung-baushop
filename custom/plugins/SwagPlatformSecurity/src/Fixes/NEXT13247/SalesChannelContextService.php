<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT13247;

use Shopware\Core\Checkout\Cart\CartRuleLoader;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\PlatformRequest;
use Shopware\Core\SalesChannelRequest;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\Security\Components\State;
use Symfony\Component\HttpFoundation\RequestStack;

class SalesChannelContextService extends \Shopware\Core\System\SalesChannel\Context\SalesChannelContextService
{
    public const CURRENCY_ID = 'currencyId';

    public const LANGUAGE_ID = 'languageId';

    public const CUSTOMER_ID = 'customerId';

    public const CUSTOMER_GROUP_ID = 'customerGroupId';

    public const BILLING_ADDRESS_ID = 'billingAddressId';

    public const SHIPPING_ADDRESS_ID = 'shippingAddressId';

    public const PAYMENT_METHOD_ID = 'paymentMethodId';

    public const SHIPPING_METHOD_ID = 'shippingMethodId';

    public const COUNTRY_ID = 'countryId';

    public const COUNTRY_STATE_ID = 'countryStateId';

    public const VERSION_ID = 'version-id';

    public const PERMISSIONS = 'permissions';

    /**
     * @var SalesChannelContextFactory
     */
    private $factory;

    /**
     * @var CartRuleLoader
     */
    private $ruleLoader;

    /**
     * @var SalesChannelContextPersister
     */
    private $contextPersister;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var State
     */
    private $state;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        array $constructorArgs,
        State $state,
        SalesChannelContextFactory $factory,
        CartRuleLoader $ruleLoader,
        \Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister $contextPersister,
        CartService $cartService,
        RequestStack $requestStack
    ) {
        // @codeCoverageIgnoreStart
        if (method_exists(get_parent_class($this), '__construct')) {
            parent::__construct(... $constructorArgs);
        }
        // @codeCoverageIgnoreEnd

        $this->factory = $factory;
        $this->ruleLoader = $ruleLoader;
        $this->contextPersister = $contextPersister;
        $this->cartService = $cartService;
        $this->state = $state;
        $this->requestStack = $requestStack;
    }


    /**
     * @deprecated tag:v6.4.0 - Parameter $currencyId will be mandatory in future implementation
     */
    public function get(string $salesChannelId, string $token, ?string $languageId = null/*, ?string $currencyId */): SalesChannelContext
    {
        $currencyId = null;
        if (\func_num_args() >= 4) {
            $currencyId = func_get_arg(3);
        }

        if (!$this->state->isActive('NEXT-13247')) {
            return parent::get($salesChannelId, $token, $languageId, $currencyId);
        }

        $parameters = $this->contextPersister->load($token, $salesChannelId);

        if ($parameters['expired'] ?? false) {
            $token = Random::getAlphanumericString(32);

            $this->updateSession($token);
        }

        if ($languageId) {
            $parameters[self::LANGUAGE_ID] = $languageId;
        }

        if (\func_num_args() >= 4 && !\array_key_exists(self::CURRENCY_ID, $parameters)) {
            $currencyId = func_get_arg(3);

            if ($currencyId !== null) {
                $parameters[self::CURRENCY_ID] = $currencyId;
            }
        }

        $context = $this->factory->create($token, $salesChannelId, $parameters);

        $result = $this->ruleLoader->loadByToken($context, $token);

        $this->cartService->setCart($result->getCart());

        return $context;
    }

    public function updateSession(string $token): void
    {
        $master = $this->requestStack->getMasterRequest();
        if (!$master) {
            return;
        }
        if (!$master->attributes->get(SalesChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST)) {
            return;
        }

        if (!$master->hasSession()) {
            return;
        }

        $session = $master->getSession();
        $session->migrate();
        $session->set('sessionId', $session->getId());

        $session->set(PlatformRequest::HEADER_CONTEXT_TOKEN, $token);
        $master->headers->set(PlatformRequest::HEADER_CONTEXT_TOKEN, $token);
    }
}

