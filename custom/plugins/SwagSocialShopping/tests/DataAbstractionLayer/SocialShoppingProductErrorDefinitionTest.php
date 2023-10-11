<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\SocialShopping\Test\DataAbstractionLayer;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\SocialShopping\Test\Helper\ServicesTrait;
use SwagSocialShopping\DataAbstractionLayer\Entity\SocialShoppingProductErrorDefinition;

class SocialShoppingProductErrorDefinitionTest extends TestCase
{
    use ServicesTrait;

    /**
     * @var EntityRepositoryInterface
     */
    private $productErrorRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    protected function setUp(): void
    {
        $container = $this->getContainer();

        /** @var EntityRepositoryInterface $productErrorRepository */
        $productErrorRepository = $container->get(\sprintf('%s.repository', SocialShoppingProductErrorDefinition::ENTITY_NAME));
        $this->productErrorRepository = $productErrorRepository;

        /** @var EntityRepositoryInterface $productRepository */
        $productRepository = $container->get(\sprintf('%s.repository', ProductDefinition::ENTITY_NAME));
        $this->productRepository = $productRepository;
    }

    public function testThatProductsWithExportErrorsAreDeletable(): void
    {
        $salesChannelId = Uuid::randomHex();
        $this->createSalesChannel($salesChannelId);

        $productId = Uuid::randomHex();
        $this->createProduct($productId);

        $productErrorId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $this->productErrorRepository->create([
            [
                'id' => $productErrorId,
                'productId' => $productId,
                'salesChannelId' => $salesChannelId,
                'errors' => [
                    [
                        'error' => 'swag-social-shopping.validation.product-image.no-image',
                        'params' => [],
                    ],
                ],
            ],
        ], $context);

        $this->productRepository->delete([
            [
                'id' => $productId,
            ],
        ], $context);

        static::assertNull($this->productErrorRepository->search(new Criteria([$productErrorId]), $context)->first());
    }
}
