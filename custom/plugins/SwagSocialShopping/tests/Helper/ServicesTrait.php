<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\SocialShopping\Test\Helper;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\System\Snippet\Aggregate\SnippetSet\SnippetSetDefinition;
use Shopware\Core\Test\TestDefaults;
use SwagSocialShopping\Component\Network\Pinterest;
use SwagSocialShopping\DataAbstractionLayer\Entity\SocialShoppingSalesChannelDefinition;

trait ServicesTrait
{
    use IntegrationTestBehaviour;

    protected function createSocialShoppingSalesChannel(string $socialShoppingSalesChannelId, array $additionalData = []): void
    {
        /** @var EntityRepositoryInterface $socialShoppingSalesChannelRepository */
        $socialShoppingSalesChannelRepository = $this->getContainer()->get(\sprintf('%s.repository', SocialShoppingSalesChannelDefinition::ENTITY_NAME));
        $data = [
            'id' => $socialShoppingSalesChannelId,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'salesChannelDomain' => [
                'url' => 'http://example.com',
                'salesChannelId' => TestDefaults::SALES_CHANNEL,
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'currencyId' => Defaults::CURRENCY,
                'snippetSetId' => $this->getValidSnippetSetId(),
            ],
            'currencyId' => Defaults::CURRENCY,
            'network' => Pinterest::class,
        ];

        $data = \array_merge($data, $additionalData);

        $socialShoppingSalesChannelRepository->create([$data], Context::createDefaultContext());
    }

    protected function createSalesChannel(string $id, array $additionalData = []): void
    {
        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get(\sprintf('%s.repository', SalesChannelDefinition::ENTITY_NAME));
        $data = [
            'id' => $id,
            'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            'languageId' => Defaults::LANGUAGE_SYSTEM,
            'languages' => $additionalData['languages'] ?? [['id' => Defaults::LANGUAGE_SYSTEM]],
            'customerGroupId' => $this->getValidCustomerGroupId(),
            'currencyId' => Defaults::CURRENCY,
            'paymentMethodId' => $this->getValidPaymentMethodId(),
            'shippingMethodId' => $this->getValidShippingMethodId(),
            'countryId' => $this->getValidCountryId(),
            'navigationCategoryId' => $this->getValidCategoryId(),
            'accessKey' => 'testAccessKey',
            'name' => 'Test SalesChannel',
        ];

        $data = \array_merge($data, $additionalData);

        $salesChannelRepository->create([$data], Context::createDefaultContext());
    }

    protected function createProduct(string $productId, ?string $taxId = null, array $additionalData = []): void
    {
        /** @var EntityRepositoryInterface $productRepository */
        $productRepository = $this->getContainer()->get(\sprintf('%s.repository', ProductDefinition::ENTITY_NAME));

        $productData = [
            'id' => $productId,
            'stock' => \random_int(1, 5),
            'taxId' => $taxId ?? $this->getValidTaxId(),
            'price' => [
                'net' => [
                    'currencyId' => Defaults::CURRENCY,
                    'net' => 74.49,
                    'gross' => 89.66,
                    'linked' => true,
                ],
            ],
            'productNumber' => 'test-234',
            'translations' => [
                Defaults::LANGUAGE_SYSTEM => [
                    'name' => 'example-product',
                ],
            ],
            'visibilities' => [
                [
                    'salesChannelId' => TestDefaults::SALES_CHANNEL,
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ],
            ],
        ];

        $productData = \array_merge($productData, $additionalData);

        $productRepository->create(
            [
                $productData,
            ],
            Context::createDefaultContext()
        );
    }

    protected function getValidCustomerGroupId(): string
    {
        /** @var EntityRepositoryInterface $customerGroupRepository */
        $customerGroupRepository = $this->getContainer()->get(\sprintf('%s.repository', CustomerGroupDefinition::ENTITY_NAME));
        $customerGroupId = $customerGroupRepository->searchIds(new Criteria(), Context::createDefaultContext())->firstId();
        if ($customerGroupId === null) {
            throw new \RuntimeException('No customer group id could be found');
        }

        return $customerGroupId;
    }

    protected function getValidSnippetSetId(): string
    {
        /** @var EntityRepositoryInterface $snippetSetRepository */
        $snippetSetRepository = $this->getContainer()->get(\sprintf('%s.repository', SnippetSetDefinition::ENTITY_NAME));

        $snippetSetId = $snippetSetRepository->searchIds(new Criteria(), Context::createDefaultContext())->firstId();
        if ($snippetSetId === null) {
            throw new \RuntimeException('No snippet set found.');
        }

        return $snippetSetId;
    }
}
