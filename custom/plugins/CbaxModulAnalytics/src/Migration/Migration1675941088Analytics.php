<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;

class Migration1675941088Analytics extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1675941088;
    }

    public function update(Connection $connection): void
    {
        $configFields = array (
            array(
                'name' => 'customer_online',
                'group_name' => 'visitors',
                'label' => 'cbax-analytics.view.customerOnline.titleTree',
                'route_name' => 'cbax.analytics.getCustomerOnline',
                'path_info' => '/cbax/analytics/getCustomerOnline',
                'position' => 52,
                'active' => 1,
                'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 0, "showTable": 0, "showChart": 0, "position": 52}, "componentName": "cbax-analytics-index-customer-online"}'),
        );

        $created_at = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        foreach($configFields as $field) {

            $sql = "SELECT `id` FROM `cbax_analytics_config` WHERE `name` = '" . $field['name'] . "'";
            $found = $connection->executeQuery($sql)->fetch();

            if (!empty($found)) {
                continue;
            }

            $connection->executeUpdate('
                INSERT IGNORE INTO `cbax_analytics_config`
                    (`id`, `name`, `group_id`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, NULL, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)',
                [
                    'id' => Uuid::randomBytes(),
                    'name' => $field['name'],
                    'group_name' => $field['group_name'],
                    'label' => $field['label'],
                    'route_name' => $field['route_name'],
                    'path_info' => $field['path_info'],
                    'position' => $field['position'],
                    'active' => $field['active'],
                    'parameter' => $field['parameter'],
                    'created_at' => $created_at
                ]
            );
        }

        $connection->executeUpdate("UPDATE `cbax_analytics_config` as c
            SET c.group_id=(SELECT g.id FROM `cbax_analytics_groups_config` as g
            WHERE g.name='visitors' LIMIT 1) WHERE c.name IN
            ('customer_online');
        ");
    }

    public function updateDestructive(Connection $connection): void
    {

    }
}


