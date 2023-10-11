<?php declare(strict_types=1);

/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\TestDefaults;
use Swag\SocialShopping\Test\Helper\MigrationTemplateTestHelper;
use SwagSocialShopping\Migration\Migration1630669686FixProductExportWithMissingImages;

class Migration1630669686FixProductExportWithMissingImagesTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MigrationTemplateTestHelper;

    /**
     * @var Connection
     */
    private $connection;

    private Migration1630669686FixProductExportWithMissingImages $migration;

    private string $salesChannelDomainId;

    private string $socialSalesChannelDomainId;

    private string $salesChannelId;

    private string $socialSalesChannelId;

    //the sales channel assigned to the social sales channel
    private string $socialSalesChannelSalesChannelId;

    private string $productStreamId;

    private string $socialProductStreamId;

    private string $productExportId;

    private bool $setUp = false;

    protected function setUp(): void
    {
        $this->socialSalesChannelId = Uuid::randomHex();
        $this->productExportId = UUID::randomHex();

        $this->setUpOnce();
    }

    protected function tearDown(): void
    {
        $this->resetDB();
    }

    /**
     * @dataProvider migrationCases
     */
    public function testMigration(array $data): void
    {
        $this->setDB($this->prepareDataForDbCreation($data));

        $bodyBeforeMigration = $this->getExportBody($this->productExportId);

        $this->migration->update($this->connection);

        if ($data['expectChanges']) {
            static::assertMatchesRegularExpression('/{%- if product.cover -%}/', $this->getExportBody($this->productExportId));

            return;
        }

        static::assertSame($bodyBeforeMigration, $this->getExportBody($this->productExportId));
    }

    public function migrationCases(): array
    {
        return [
            [['old_body' => '', 'isSocialExport' => false, 'expectChanges' => false]],
            [['old_body' => '', 'isSocialExport' => true, 'expectChanges' => false]],
            [['old_body' => Migration1630669686FixProductExportWithMissingImages::OLD_TEMPLATE, 'isSocialExport' => true, 'expectChanges' => true]],
            [['old_body' => Migration1630669686FixProductExportWithMissingImages::OLD_TEMPLATE, 'isSocialExport' => false, 'expectChanges' => false]],
            [['old_body' => Migration1630669686FixProductExportWithMissingImages::NEW_TEMPLATE, 'isSocialExport' => true, 'expectChanges' => false]],
        ];
    }

    private function setUpOnce(): void
    {
        if ($this->setUp) {
            return;
        }
        /** @var Connection $connection */
        $connection = $this->getContainer()->get(Connection::class);

        $this->connection = $connection;

        $this->migration = new Migration1630669686FixProductExportWithMissingImages();

        $getSalesChannelDomainIdsSQL = '
        SELECT HEX(id) AS id
        FROM sales_channel_domain
        WHERE HEX(sales_channel_id)';

        $this->socialSalesChannelSalesChannelId = TestDefaults::SALES_CHANNEL;

        $getSecondSalesChannelDomainIdSQL = $getSalesChannelDomainIdsSQL . ' != ?';

        $this->socialSalesChannelDomainId = $this->getSalesChannelDomainId(TestDefaults::SALES_CHANNEL);

        /** @var array $secondSalesChannelDomain */
        $secondSalesChannelDomain = $this->connection->fetchAssociative($getSecondSalesChannelDomainIdSQL, [TestDefaults::SALES_CHANNEL]);
        $this->salesChannelDomainId = $secondSalesChannelDomain['id'];

        $this->salesChannelId = TestDefaults::SALES_CHANNEL;

        $this->socialProductStreamId = $this->createProductStream(Uuid::randomHex());

        $this->productStreamId = $this->createProductStream(Uuid::randomHex());

        $this->setUp = true;
    }

    private function prepareDataForDbCreation(array $data): array
    {
        return [
            'id' => $this->productExportId,
            'product_stream_id' => $data['isSocialExport'] ? $this->socialProductStreamId : $this->productStreamId,
            'salesChannelId' => $data['isSocialExport'] ? $this->socialSalesChannelSalesChannelId : $this->salesChannelId,
            'sales_channel_domain_id' => $data['isSocialExport'] ? $this->socialSalesChannelDomainId : $this->salesChannelDomainId,
            'body_template' => $data['old_body'],
        ];
    }

    private function setDB(array $ids): void
    {
        $this->createSocialSalesChannel(
            [
                'id' => $this->socialSalesChannelId,
                'salesChannelId' => $this->socialSalesChannelSalesChannelId,
                'product_stream_id' => $this->socialProductStreamId,
                'sales_channel_domain_id' => $this->socialSalesChannelDomainId,
            ]
        );

        $this->createProductExport($ids);
    }

    private function resetDB(): void
    {
        $deleteSocialChannelSQL = '
           DELETE FROM swag_social_shopping_sales_channel WHERE id = ?
        ';

        $deleteProductExportSQL = '
            DELETE FROM product_export WHERE id = ?
        ';

        $this->connection->executeStatement($deleteSocialChannelSQL, [$this->socialSalesChannelId]);
        $this->connection->executeStatement($deleteProductExportSQL, [$this->productExportId]);
    }
}
