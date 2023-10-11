<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Swag\SocialShopping\Test\Helper\MigrationTemplateTestHelper;
use SwagSocialShopping\Migration\Migration1652180224CreateTablesWithReferralCodes;

class Migration1652180224CreateTablesWithReferralCodesTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MigrationTemplateTestHelper;

    private const REFERRAL_CODE = 'referral_code';
    private const SOC_CUSTOMER_TABLE = 'swag_social_shopping_customer';
    private const SOC_ORDER_TABLE = 'swag_social_shopping_order';
    private const SOC_SALES_CHANNEL_TABLE = 'swag_social_shopping_sales_channel';

    private AbstractSchemaManager $schemaManager;

    protected function setUp(): void
    {
        $this->schemaManager = $this->getConnection()->getSchemaManager();

        $this->dropTables();
    }

    public function testMigrationCreatesTablesWithReferralCodes(): void
    {
        $connection = $this->getConnection();
        $migration = new Migration1652180224CreateTablesWithReferralCodes();
        $migration->update($connection);

        static::assertTrue($this->schemaManager->tablesExist(self::SOC_CUSTOMER_TABLE));
        static::assertTrue($this->schemaManager->tablesExist(self::SOC_ORDER_TABLE));

        $columns = $this->schemaManager->listTableColumns(self::SOC_CUSTOMER_TABLE);
        static::assertArrayHasKey(self::REFERRAL_CODE, $columns);

        $columns = $this->schemaManager->listTableColumns(self::SOC_ORDER_TABLE);
        static::assertArrayHasKey(self::REFERRAL_CODE, $columns);
    }

    private function dropTables(): void
    {
        $this->schemaManager->dropTable(self::SOC_CUSTOMER_TABLE);
        static::assertFalse($this->schemaManager->tablesExist(self::SOC_CUSTOMER_TABLE));

        $this->schemaManager->dropTable(self::SOC_ORDER_TABLE);
        static::assertFalse($this->schemaManager->tablesExist(self::SOC_ORDER_TABLE));
    }
}
