<?php declare(strict_types=1);

namespace Swag\SocialShopping\Test\Migration;

/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestDataCollection;
use Shopware\Core\Test\TestDefaults;
use Swag\SocialShopping\Test\Helper\MigrationTemplateTestHelper;
use SwagSocialShopping\Migration\Migration1653941543AddReferralCodeToTemplates;

class Migration1653941543AddReferralCodeToTemplatesTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MigrationTemplateTestHelper;

    private TestDataCollection $ids;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->ids = new TestDataCollection([Context::createDefaultContext()]);

        $this->createProductStream($this->ids->get('productStreamId'));
        $this->createProductStream($this->ids->get('socialProductStreamId'));

        $this->ids->set('socialSalesChannelSalesChannelId', TestDefaults::SALES_CHANNEL);
        $this->ids->set('socialSalesChannelDomainId', $this->getSalesChannelDomainId(TestDefaults::SALES_CHANNEL));

        $this->connection = $this->getConnection();

        $getSalesChannelDomainIdsSQL = <<<SQL
            SELECT HEX(id) AS id
            FROM sales_channel_domain
            WHERE HEX(sales_channel_id) != :id
SQL;

        /** @var array $secondSalesChannelDomain */
        $secondSalesChannelDomain = $this->connection->fetchAssociative($getSalesChannelDomainIdsSQL, ['id' => TestDefaults::SALES_CHANNEL]);
        $this->ids->set('salesChannelDomainId', $secondSalesChannelDomain['id']);
    }

    /**
     * @dataProvider migrationCases
     */
    public function testMigration(string $oldBody, bool $isSocialExport, bool $expectChanges, string $newBody): void
    {
        $migration = new Migration1653941543AddReferralCodeToTemplates();

        $this->setDB($this->prepareDataForDbCreation($isSocialExport, $oldBody));

        $bodyBeforeMigration = $this->getExportBody($this->ids->get('productExportId'));

        $migration->update($this->connection);

        if ($expectChanges) {
            static::assertSame(trim($newBody), trim($this->getExportBody($this->ids->get('productExportId'))));

            return;
        }

        static::assertSame($bodyBeforeMigration, $this->getExportBody($this->ids->get('productExportId')));
    }

    public function migrationCases(): array
    {
        return [
            ['old_body' => '', 'isSocialExport' => false, 'expectChanges' => false, 'new_body' => ''],
            ['old_body' => '', 'isSocialExport' => true, 'expectChanges' => false, 'new_body' => ''],
            [
                'old_body' => Migration1653941543AddReferralCodeToTemplates::getTemplate('old_facebook'),
                'isSocialExport' => true,
                'expectChanges' => true,
                'new_body' => Migration1653941543AddReferralCodeToTemplates::getTemplate('new_facebook'),
            ],
            [
                'old_body' => Migration1653941543AddReferralCodeToTemplates::getTemplate('old_google'),
                'isSocialExport' => true,
                'expectChanges' => true,
                'new_body' => Migration1653941543AddReferralCodeToTemplates::getTemplate('new_google'),
            ],
            [
                'old_body' => Migration1653941543AddReferralCodeToTemplates::getTemplate('new_facebook'),
                'isSocialExport' => true,
                'expectChanges' => false,
                'new_body' => '',
            ],
            [
                'old_body' => Migration1653941543AddReferralCodeToTemplates::getTemplate('new_google'),
                'isSocialExport' => true,
                'expectChanges' => false,
                'new_body' => '',
            ],
            [
                'old_body' => Migration1653941543AddReferralCodeToTemplates::getTemplate('old_facebook'),
                'isSocialExport' => false,
                'expectChanges' => false,
                'new_body' => '',
            ],
            [
                'old_body' => Migration1653941543AddReferralCodeToTemplates::getTemplate('old_google'),
                'isSocialExport' => false,
                'expectChanges' => false,
                'new_body' => '',
            ],
        ];
    }

    private function prepareDataForDbCreation(bool $isSocialExport, string $oldBody): array
    {
        return [
            'id' => $this->ids->get('productExportId'),
            'product_stream_id' => $isSocialExport ? $this->ids->get('socialProductStreamId') : $this->ids->get('productStreamId'),
            'salesChannelId' => $isSocialExport ? $this->ids->get('socialSalesChannelSalesChannelId') : TestDefaults::SALES_CHANNEL,
            'sales_channel_domain_id' => $isSocialExport ? $this->ids->get('socialSalesChannelDomainId') : $this->ids->get('salesChannelDomainId'),
            'body_template' => $oldBody,
        ];
    }

    private function setDB(array $ids): void
    {
        $this->createSocialSalesChannel(
            [
                'id' => $this->ids->get('socialSalesChannelId'),
                'salesChannelId' => $this->ids->get('socialSalesChannelSalesChannelId'),
                'product_stream_id' => $this->ids->get('socialProductStreamId'),
                'sales_channel_domain_id' => $this->ids->get('socialSalesChannelDomainId'),
            ]
        );

        $this->createProductExport($ids);
    }
}
