<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\SocialShopping\Test\DataAbstractionLayer;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\SocialShopping\Test\Helper\ServicesTrait;
use SwagSocialShopping\DataAbstractionLayer\SalesChannelRepositoryDecorator;

class SalesChannelRepositoryDecoratorTest extends TestCase
{
    use ServicesTrait;

    /**
     * @var SalesChannelRepositoryDecorator
     */
    private $salesChannelRepositoryDecorator;

    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepository;

    protected function setUp(): void
    {
        $container = $this->getContainer();

        /** @var SalesChannelRepositoryDecorator $repositoryDecorator */
        $repositoryDecorator = $container->get(SalesChannelRepositoryDecorator::class);
        $this->salesChannelRepositoryDecorator = $repositoryDecorator;

        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $container->get(\sprintf('%s.repository', SalesChannelDefinition::ENTITY_NAME));
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public function testThatDecoratorReturnsOriginalDefinition(): void
    {
        static::assertInstanceOf(SalesChannelDefinition::class, $this->salesChannelRepositoryDecorator->getDefinition());
    }

    public function testDecoratorCreate(): void
    {
        $id = Uuid::randomHex();
        $this->createSalesChannel($id);

        $salesChannel = $this->salesChannelRepository->search(new Criteria([$id]), Context::createDefaultContext())->first();
        static::assertInstanceOf(SalesChannelEntity::class, $salesChannel);
    }

    public function testDecoratorUpsert(): void
    {
        $id = Uuid::randomHex();
        $this->createSalesChannel($id);

        $alteredName = 'altered-name';
        $this->salesChannelRepositoryDecorator->upsert(
            [
                [
                    'id' => $id,
                    'name' => $alteredName,
                ],
            ],
            Context::createDefaultContext()
        );

        /** @var SalesChannelEntity|null $salesChannel */
        $salesChannel = $this->salesChannelRepository->search(new Criteria([$id]), Context::createDefaultContext())->first();
        static::assertInstanceOf(SalesChannelEntity::class, $salesChannel);
        static::assertSame($alteredName, $salesChannel->getName());
    }

    public function testDecoratorUpdate(): void
    {
        $id = Uuid::randomHex();
        $this->createSalesChannel($id);

        $alteredName = 'altered-name';
        $this->salesChannelRepositoryDecorator->update(
            [
                [
                    'id' => $id,
                    'name' => $alteredName,
                ],
            ],
            Context::createDefaultContext()
        );

        /** @var SalesChannelEntity|null $salesChannel */
        $salesChannel = $this->salesChannelRepository->search(new Criteria([$id]), Context::createDefaultContext())->first();
        static::assertInstanceOf(SalesChannelEntity::class, $salesChannel);
        static::assertSame($alteredName, $salesChannel->getName());
    }

    public function testDecoratorSearchIds(): void
    {
        $idSearchResult = $this->salesChannelRepositoryDecorator->searchIds(
            new Criteria(),
            Context::createDefaultContext()
        );

        static::assertSame(2, $idSearchResult->getTotal());
    }

    public function testDecoratorSearch(): void
    {
        $id = Uuid::randomHex();
        $this->createSalesChannel($id);
        $criteria = new Criteria([$id]);

        $salesChannel = $this->salesChannelRepositoryDecorator->search(
            $criteria,
            Context::createDefaultContext()
        )->first();
        static::assertNotNull($salesChannel);
        static::assertNotNull($salesChannel);
        static::assertNotSame($criteria, $criteria->getAssociation('socialShoppingSalesChannel'));
    }
}
