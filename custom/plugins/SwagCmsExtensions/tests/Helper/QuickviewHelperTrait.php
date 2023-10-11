<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Helper;

use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Tax\TaxEntity;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Symfony\Component\HttpFoundation\Request;

trait QuickviewHelperTrait
{
    /**
     * @var array<string, string>
     */
    private $optionIds;

    /**
     * @var array<string, string>
     */
    private $groupIds;

    /**
     * @var string
     */
    private $productId;

    private function getRandomOptions(string $groupId): string
    {
        return \json_encode([
            $groupId => Uuid::randomHex(),
            Uuid::randomHex() => Uuid::randomHex(),
            Uuid::randomHex() => Uuid::randomHex(),
        ]) ?: '';
    }

    private function getVariantOptions(): string
    {
        return \json_encode([
            $this->groupIds['color'] => $this->optionIds['red'],
            $this->groupIds['size'] => $this->optionIds['xl'],
        ]) ?: '';
    }

    private function getVariantRequest(string $options, SalesChannelContext $salesChannelContext): Request
    {
        $request = new Request([
            'options' => $options,
            'switched' => $this->groupIds['color'],
            'parentId' => $this->productId,
        ], [], [
            'productId' => $this->productId,
        ]);

        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $salesChannelContext);
        $request->attributes->set(RequestTransformer::STOREFRONT_URL, '');

        return $request;
    }

    private function createProduct(SalesChannelContext $salesChannelContext): void
    {
        $this->productId = Uuid::randomHex();

        /** @var TaxEntity $tax */
        $tax = $salesChannelContext->getTaxRules()->first();

        $this->optionIds = [
            'red' => Uuid::randomHex(),
            'green' => Uuid::randomHex(),
            'xl' => Uuid::randomHex(),
            'l' => Uuid::randomHex(),
        ];

        $variantIds = [
            'redXl' => Uuid::randomHex(),
            'greenXl' => Uuid::randomHex(),
            'redL' => Uuid::randomHex(),
            'greenL' => Uuid::randomHex(),
        ];

        $this->groupIds = [
            'color' => Uuid::randomHex(),
            'size' => Uuid::randomHex(),
        ];

        $data = [
            [
                'id' => $this->productId,
                'name' => 'Test product',
                'productNumber' => 'a.0',
                'manufacturer' => ['name' => 'test'],
                'taxId' => $tax->getId(),
                'stock' => 10,
                'active' => true,
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => true]],
                'configuratorSettings' => [
                    [
                        'option' => [
                            'id' => $this->optionIds['red'],
                            'name' => 'Red',
                            'group' => [
                                'id' => $this->groupIds['color'],
                                'name' => 'Color',
                            ],
                        ],
                    ],
                    [
                        'option' => [
                            'id' => $this->optionIds['green'],
                            'name' => 'Green',
                            'group' => [
                                'id' => $this->groupIds['color'],
                                'name' => 'Color',
                            ],
                        ],
                    ],
                    [
                        'option' => [
                            'id' => $this->optionIds['xl'],
                            'name' => 'XL',
                            'group' => [
                                'id' => $this->groupIds['size'],
                                'name' => 'size',
                            ],
                        ],
                    ],
                    [
                        'option' => [
                            'id' => $this->optionIds['l'],
                            'name' => 'L',
                            'group' => [
                                'id' => $this->groupIds['size'],
                                'name' => 'size',
                            ],
                        ],
                    ],
                ],
                'visibilities' => [
                    [
                        'salesChannelId' => $salesChannelContext->getSalesChannel()->getId(),
                        'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                    ],
                ],
            ],
            [
                'id' => $variantIds['redXl'],
                'productNumber' => 'a.1',
                'stock' => 10,
                'active' => true,
                'parentId' => $this->productId,
                'options' => [
                    ['id' => $this->optionIds['red']],
                    ['id' => $this->optionIds['xl']],
                ],
            ],
            [
                'id' => $variantIds['greenXl'],
                'productNumber' => 'a.2',
                'stock' => 10,
                'active' => true,
                'parentId' => $this->productId,
                'options' => [
                    ['id' => $this->optionIds['green']],
                    ['id' => $this->optionIds['xl']],
                ],
            ],
            [
                'id' => $variantIds['redL'],
                'productNumber' => 'a.3',
                'stock' => 10,
                'active' => true,
                'parentId' => $this->productId,
                'options' => [
                    ['id' => $this->optionIds['red']],
                    ['id' => $this->optionIds['l']],
                ],
            ],
            [
                'id' => $variantIds['greenL'],
                'productNumber' => 'a.4',
                'stock' => 10,
                'active' => true,
                'parentId' => $this->productId,
                'options' => [
                    ['id' => $this->optionIds['green']],
                    ['id' => $this->optionIds['l']],
                ],
            ],
        ];

        $productRepository = $this->getContainer()->get('product.repository');
        static::assertInstanceOf(EntityRepositoryInterface::class, $productRepository);
        $productRepository->create($data, $salesChannelContext->getContext());
    }
}
