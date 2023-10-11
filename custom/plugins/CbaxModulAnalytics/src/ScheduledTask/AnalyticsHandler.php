<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Doctrine\DBAL\Connection;
use Cbax\ModulAnalytics\Components\ConfigReaderHelper;

class AnalyticsHandler extends ScheduledTaskHandler
{
    /**
     * @var array|null
     */
    private $config = null;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ConfigReaderHelper
     */
    private $configReaderHelper;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        Connection $connection,
        ConfigReaderHelper $configReaderHelper
        )
    {
        parent::__construct($scheduledTaskRepository);

        $this->connection = $connection;
        $this->configReaderHelper = $configReaderHelper;
    }

    public static function getHandledMessages(): iterable
    {
        return [ Analytics::class ];
    }

    public function run(): void
    {
        $this->config = $this->config ?? $this->configReaderHelper->getConfig();

        try {

            $deleteSearchTime = !empty($this->config['deleteSearchTime']) ? $this->config['deleteSearchTime'] : 180;
            if ($deleteSearchTime != 1) {
                $sql = "DELETE FROM `cbax_analytics_search` WHERE DATEDIFF(NOW(), `created_at`) > ?;";
                $this->connection->executeUpdate($sql, [$deleteSearchTime]);
            }

            $sql = "DELETE FROM `cbax_analytics_pool` WHERE DATEDIFF(NOW(), `date`) > 1;";
            $this->connection->executeUpdate($sql, [$deleteSearchTime]);

            //Visitor-Tabellen leeren
            $deleteVisitorsTime = !empty($this->config['deleteVisitorsTime']) ? $this->config['deleteVisitorsTime'] : 180;
            if ($deleteVisitorsTime != 1) {
                $sql = "DELETE FROM `cbax_analytics_visitor` WHERE DATEDIFF(NOW(), `created_at`) > ?;";
                $this->connection->executeUpdate($sql, [$deleteVisitorsTime]);

                $sql = "DELETE FROM `cbax_analytics_visitor_impressions` WHERE DATEDIFF(NOW(), `created_at`) > ?;";
                $this->connection->executeUpdate($sql, [$deleteVisitorsTime]);

                $sql = "DELETE FROM `cbax_analytics_product_impressions` WHERE DATEDIFF(NOW(), `created_at`) > ?;";
                $this->connection->executeUpdate($sql, [$deleteVisitorsTime]);

                $sql = "DELETE FROM `cbax_analytics_category_impressions` WHERE DATEDIFF(NOW(), `created_at`) > ?;";
                $this->connection->executeUpdate($sql, [$deleteVisitorsTime]);

                $sql = "DELETE FROM `cbax_analytics_manufacturer_impressions` WHERE DATEDIFF(NOW(), `created_at`) > ?;";
                $this->connection->executeUpdate($sql, [$deleteVisitorsTime]);
            }
        } catch (\Throwable $e) {
            // catch exception - otherwise the task will never be called again
        }

    }
}
