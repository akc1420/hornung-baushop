<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\SocialShopping\Test\EventListener;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Test\Customer\Rule\OrderFixture;
use Shopware\Core\Content\Test\Flow\OrderActionTrait;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestDataCollection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\TestDefaults;
use Shopware\Storefront\Test\Controller\StorefrontControllerTestBehaviour;
use SwagSocialShopping\DataAbstractionLayer\Entity\SocialShoppingCustomerEntity;
use SwagSocialShopping\EventListener\ReferralCodeHandler;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class ReferralCodeHandlerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontControllerTestBehaviour;
    use OrderActionTrait;
    use OrderFixture;

    private const CUSTOMER_EXTENSION_NAME = 'swagSocialShoppingCustomer';
    private const ORDER_EXTENSION_NAME = 'swagSocialShoppingOrder';
    private const REFERRAL_CODE_KEY = 'referralCode';

    protected function setUp(): void
    {
        $this->ids = new TestDataCollection([Context::createDefaultContext()]);
        $this->ids->set('code', TestDefaults::SALES_CHANNEL);
        $this->browser = $this->createSalesChannelBrowser(null, false, [
            'id' => $this->ids->get('sales-channel'),
        ]);
    }

    public function testHandlerCreatesReferralCodesWhileRegistration(): void
    {
        $this->register();

        $customer = $this->fetchCustomer();
        /** @var SocialShoppingCustomerEntity $socCustomer */
        $socCustomer = $customer->getExtension(self::CUSTOMER_EXTENSION_NAME);

        static::assertSame($customer->getId(), $socCustomer->getCustomerId());
        static::assertSame($this->ids->get('code'), $socCustomer->getReferralCode());
    }

    public function testReferralCodeHandlerCanBeCalledTwice(): void
    {
        $paymentMethodId = $this->addNewPaymentMethod();

        $this->prepareProductTest();
        $this->createCustomer($this->getValidPaymentMethodId());
        $this->login($this->getEmail(), $this->ids->get('password'));
        $this->order();

        $responseContent = $this->orderUpdate($this->getOrder()->getId(), $paymentMethodId)->getContent() ?: 'response content is empty';

        static::assertStringContainsString('changedPayment=1', $responseContent);
    }

    public function testHandlerCreatesReferralCodesForOrder(): void
    {
        $this->prepareProductTest();
        $this->createCustomer();
        $this->login($this->getEmail(), $this->ids->get('password'));
        $this->order();

        $order = $this->getOrder();
        $socOrder = $order->getExtension(self::ORDER_EXTENSION_NAME);

        static::assertSame($order->getId(), $socOrder->getOrderId());
        static::assertSame($order->getVersionId(), $socOrder->getOrderVersionId());
        static::assertSame($this->ids->get('code'), $socOrder->getReferralCode());
    }

    public function testIsValidReferralCode(): void
    {
        $definitionInstanceRegistryMock = $this->createMock(DefinitionInstanceRegistry::class);
        $definitionInstanceRegistryMock->method('getRepository')->willReturn($this->getContainer()->get('sales_channel.repository'));

        $referralCodeHandler = new ReferralCodeHandler(
            $definitionInstanceRegistryMock,
            $this->createMock(RequestStack::class)
        );
        $method = new \ReflectionMethod(ReferralCodeHandler::class, 'isValidReferralCode');
        $method->setAccessible(true);

        $isValid = $method->invokeArgs($referralCodeHandler, ['', Context::createDefaultContext()]);
        static::assertFalse($isValid);

        $invalidId = Uuid::randomHex();
        $isValid = $method->invokeArgs($referralCodeHandler, [$invalidId, Context::createDefaultContext()]);
        static::assertFalse($isValid);

        $isValid = $method->invokeArgs($referralCodeHandler, ['StringNotUuid', Context::createDefaultContext()]);
        static::assertFalse($isValid);

        $id = Uuid::randomHex();
        $this->createSalesChannel(['id' => $id]);
        $isValid = $method->invokeArgs($referralCodeHandler, [$id, Context::createDefaultContext()]);
        static::assertTrue($isValid);
    }

    private function createCustomer(?string $paymentId = null): void
    {
        $this->ids->set('address', $this->getValidShippingMethodId());
        $this->ids->set('payment', $paymentId ?: $this->getValidPaymentMethodId());

        $this->getContainer()->get('customer.repository')->create([
            [
                'id' => $this->ids->get('customer'),
                'salesChannelId' => $this->ids->get('sales-channel'),
                'defaultShippingAddressId' => $this->ids->get('address'),
                'defaultShippingAddress' => [
                    'id' => $this->ids->get('address'),
                    'firstName' => 'Max',
                    'lastName' => 'Mustermann',
                    'street' => 'Musterstraße 1',
                    'city' => 'Schöppingen',
                    'zipcode' => '12345',
                    'salutationId' => $this->getValidSalutationId(),
                    'countryId' => $this->getValidCountryId($this->ids->get('sales-channel')),
                ],
                'defaultBillingAddressId' => $this->ids->get('address'),
                'defaultPaymentMethodId' => $this->ids->get('payment'),
                'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
                'email' => $this->getEmail(),
                'password' => $this->ids->get('password'),
                'firstName' => 'Max',
                'lastName' => 'Mustermann',
                'guest' => false,
                'salutationId' => $this->getValidSalutationId(),
                'customerNumber' => '12345',
            ],
        ], Context::createDefaultContext());
    }

    private function addNewPaymentMethod(?string $paymentId = null, ?string $name = 'test payment method', bool $active = true): string
    {
        $paymentMethodRepository = $this->getContainer()->get('payment_method.repository');

        $paymentMethodId = $paymentId ?: Uuid::randomHex();

        $result = $paymentMethodRepository->upsert([
            [
                'id' => $paymentMethodId,
                'name' => $name,
                'active' => $active,
            ],
        ], Context::createDefaultContext());

        /**
         * @var EntityRepositoryInterface
         */
        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');

        $salesChannelRepository->upsert([[
            'id' => $this->ids->get('sales-channel'),
            'paymentMethods' => [['id' => $paymentMethodId]],
        ]], Context::createDefaultContext());

        return $paymentMethodId;
    }

    private function getOrder(): OrderEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociation(self::ORDER_EXTENSION_NAME)->addFilter(new EqualsFilter(
            'salesChannelId',
            $this->ids->get('sales-channel')
        ));

        $order = $this->getContainer()->get('order.repository')
            ->search($criteria, $this->ids->context)
            ->first();

        return $order;
    }

    private function fetchCustomer(): CustomerEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociation(self::CUSTOMER_EXTENSION_NAME)->addFilter(new EqualsFilter(
            'email',
            $this->getEmail()
        ));

        return $this->getContainer()->get('customer.repository')
            ->search($criteria, $this->ids->context)
            ->first();
    }

    private function register(): void
    {
        $data = $this->getRegistrationData();

        $this->request(
            'POST',
            '/account/register',
            $this->tokenize('frontend.account.register.save', $data)
        );
    }

    private function getRegistrationData(): array
    {
        return [
            'accountType' => CustomerEntity::ACCOUNT_TYPE_PRIVATE,
            'email' => $this->getEmail(),
            'emailConfirmation' => $this->getEmail(),
            'password' => 'shopware',
            'salutationId' => $this->getValidSalutationId(),
            'firstName' => 'Max',
            'lastName' => 'Mustermann',
            'storefrontUrl' => 'http://localhost',
            'errorRoute' => 'foo',
            'billingAddress' => [
                'countryId' => $this->getValidCountryId(),
                'street' => 'Musterstraße 13',
                'zipcode' => '48153',
                'city' => 'Münster',
            ],
            self::REFERRAL_CODE_KEY => $this->ids->get('code'),
        ];
    }

    private function login(string $email, string $password): void
    {
        $this->browser->request(
            'POST',
            '/account/login',
            $this->tokenize('frontend.account.login', [
                'email' => $email,
                'password' => $password,
            ])
        );
    }

    private function order(): void
    {
        $this->browser
            ->request(
                'POST',
                '/checkout/line-item/add',
                $this->tokenize('frontend.checkout.line-item.add', [
                    'lineItems' => [
                        [
                            'id' => $this->ids->get('p1'),
                            'quantity' => 3,
                            'type' => 'product',
                            'referencedId' => $this->ids->get('p1'),
                        ],
                    ],
                ])
            );

        $this->browser
            ->request(
                'POST',
                '/checkout/order',
                $this->tokenize('frontend.checkout.finish.order', [
                    'tos' => true,
                    self::REFERRAL_CODE_KEY => $this->ids->get('code'),
                ]),
            );
    }

    private function orderUpdate(string $orderId, string $paymentId): Response
    {
        $this->browser
            ->request(
                'POST',
                '/account/order/update/' . $orderId,
                $this->tokenize('frontend.account.order.update', [
                    'tos' => true,
                    'paymentMethodId' => $paymentId,
                    'customerComment' => 'Test',
                ]),
            );

        return $this->browser->getResponse();
    }

    private function getEmail(): string
    {
        return $this->ids->get('email') . '@example.com';
    }
}
