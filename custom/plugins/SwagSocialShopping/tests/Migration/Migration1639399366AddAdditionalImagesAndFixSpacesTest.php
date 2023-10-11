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
use SwagSocialShopping\Migration\Migration1639399366AddAdditionalImagesAndFixSpaces;

class Migration1639399366AddAdditionalImagesAndFixSpacesTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MigrationTemplateTestHelper;

    private string $productStreamId;

    private string $productExportId;

    private string $salesChannelDomainId;

    private string $socialProductStreamId;

    private string $socialSalesChannelId;

    private string $socialSalesChannelSalesChannelId;

    private string $socialSalesChannelDomainId;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->productStreamId = $this->createProductStream(Uuid::randomHex());
        $this->productExportId = Uuid::randomHex();
        $this->socialSalesChannelId = Uuid::randomHex();
        $this->socialSalesChannelSalesChannelId = TestDefaults::SALES_CHANNEL;
        $this->connection = $this->getConnection();

        $this->socialSalesChannelDomainId = $this->getSalesChannelDomainId(TestDefaults::SALES_CHANNEL);

        $this->socialProductStreamId = $this->createProductStream(Uuid::randomHex());

        $getSalesChannelDomainIdsSQL = '
        SELECT HEX(id) AS id
        FROM sales_channel_domain
        WHERE HEX(sales_channel_id) != :id';

        /** @var array $secondSalesChannelDomain */
        $secondSalesChannelDomain = $this->connection->fetchAssociative($getSalesChannelDomainIdsSQL, ['id' => TestDefaults::SALES_CHANNEL]);
        $this->salesChannelDomainId = $secondSalesChannelDomain['id'];
    }

    /**
     * @dataProvider migrationCases
     */
    public function testMigration(string $oldBody, bool $isSocialExport, bool $expectChanges, string $newBody): void
    {
        $migration = new Migration1639399366AddAdditionalImagesAndFixSpaces();

        $this->setDB($this->prepareDataForDbCreation($isSocialExport, $oldBody));

        $bodyBeforeMigration = $this->getExportBody($this->productExportId);

        $migration->update($this->connection);

        if ($expectChanges) {
            static::assertSame(trim($newBody), trim($this->getExportBody($this->productExportId)));

            return;
        }

        static::assertSame($bodyBeforeMigration, $this->getExportBody($this->productExportId));
    }

    public function migrationCases(): array
    {
        return [
            ['old_body' => '', 'isSocialExport' => false, 'expectChanges' => false, ''],
            ['old_body' => '', 'isSocialExport' => true, 'expectChanges' => false, ''],
            [
                'old_body' => Migration1639399366AddAdditionalImagesAndFixSpaces::getTemplate('old_facebook'),
                'isSocialExport' => true,
                'expectChanges' => true,
                'new_body' => Migration1639399366AddAdditionalImagesAndFixSpaces::getTemplate('new_facebook'),
            ],
            ['old_body' => '', 'isSocialExport' => false, 'expectChanges' => false, ''],
            ['old_body' => '', 'isSocialExport' => true, 'expectChanges' => false, ''],
            [
                'old_body' => Migration1639399366AddAdditionalImagesAndFixSpaces::getTemplate('oldest_facebook'),
                'isSocialExport' => true,
                'expectChanges' => true,
                'new_body' => Migration1639399366AddAdditionalImagesAndFixSpaces::getTemplate('new_facebook'),
            ],
            [
                'old_body' => Migration1639399366AddAdditionalImagesAndFixSpaces::getTemplate('old_google'),
                'isSocialExport' => true,
                'expectChanges' => true,
                'new_body' => Migration1639399366AddAdditionalImagesAndFixSpaces::getTemplate('new_google'),
            ],
            [
                'old_body' => Migration1639399366AddAdditionalImagesAndFixSpaces::getTemplate('new_facebook'),
                'isSocialExport' => true,
                'expectChanges' => false,
                '',
            ],
            [
                'old_body' => Migration1639399366AddAdditionalImagesAndFixSpaces::getTemplate('new_google'),
                'isSocialExport' => true,
                'expectChanges' => false,
                '',
            ],
            [
                'old_body' => Migration1639399366AddAdditionalImagesAndFixSpaces::getTemplate('old_facebook'),
                'isSocialExport' => false,
                'expectChanges' => false,
                '',
            ],
            [
                'old_body' => Migration1639399366AddAdditionalImagesAndFixSpaces::getTemplate('old_google'),
                'isSocialExport' => false,
                'expectChanges' => false,
                '',
            ],
        ];
    }

    private function prepareDataForDbCreation(bool $isSocialExport, string $oldBody): array
    {
        return [
            'id' => $this->productExportId,
            'product_stream_id' => $isSocialExport ? $this->socialProductStreamId : $this->productStreamId,
            'salesChannelId' => $isSocialExport ? $this->socialSalesChannelSalesChannelId : TestDefaults::SALES_CHANNEL,
            'sales_channel_domain_id' => $isSocialExport ? $this->socialSalesChannelDomainId : $this->salesChannelDomainId,
            'body_template' => $oldBody,
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
}
