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
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\SocialShopping\Test\Helper\MigrationTemplateTestHelper;
use SwagSocialShopping\Migration\Migration1659081754AddAclRights;

class Migration1659081754AddAclRightsTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MigrationTemplateTestHelper;

    public const oldRoleSQL = "
    INSERT INTO `acl_role` (`id`, `name`, `description`, `privileges`, `created_at`, `updated_at`, `deleted_at`)
    VALUES
    (UNHEX('BF232DEBB4ED44CBADFE66CDCED0EB9D'),
    'Test12',
    	NULL,
    	'[
    	      \"customer.viewer\",
    	      \"customer:read\",
    	      \"customer_address:read\",
    	      \"customer_group:read\",
    	      \"order.viewer\",
              \"order:read\"
        ]',
        '2022-07-29 08:11:57.121',
    	'2022-07-29 00:00:00.000',
    	NULL);";

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = $this->getConnection();

//        $this->connection->executeQuery(self::oldRoleSQL);
    }

    public function testMigration(): void
    {
        $migration = new Migration1659081754AddAclRights();

        $repo = $this->getContainer()->get('acl_role.repository');
        $id = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $repo->create([[
            'id' => $id,
            'name' => 'test',
            'privileges' => ['order.viewer', 'customer.viewer'],
        ]], $context);

        $migration->update($this->connection);

        $role = $repo->search(new Criteria([$id]), $context)->first();

        static::assertNotNull($role);

        static::assertContains('swag_social_shopping_order:read', $role->getPrivileges());
        static::assertContains('sales_channel_type:read', $role->getPrivileges());
        static::assertContains('swag_social_shopping_customer:read', $role->getPrivileges());
    }
}
