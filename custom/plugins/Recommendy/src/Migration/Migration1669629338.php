<?php declare(strict_types=1);

namespace Recommendy\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1669629338 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1669629338;
    }

    public function update(Connection $connection): void
    {
        try {
            $connection->executeStatement('
                CREATE TABLE IF NOT EXISTS `recommendy_bundle_matrix`
                (
                    `id`         BINARY(16)   NOT NULL,
                    `pon`        VARCHAR(255) NOT NULL,
                    `son`        VARCHAR(255) NOT NULL,
                    `shop`       VARCHAR(255) NOT NULL,
                    `similarity` DECIMAL(5,4) NOT NULL,
                    `created_at` DATETIME(3)  NOT NULL,
                    `updated_at` DATETIME(3)  NULL,
                    CONSTRAINT PON_SON_UNIQUE UNIQUE (pon, son, shop)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ');
            $connection->executeStatement('
                CREATE TABLE IF NOT EXISTS `recommendy_identifier`
                (
                    `id`         BINARY(16)   NOT NULL,
                    `pon`        VARCHAR(255) NOT NULL,
                    `identifier` VARCHAR(255) NOT NULL,
                    `created_at` DATETIME(3)  NOT NULL,
                    `updated_at` DATETIME(3)  NULL,
                    CONSTRAINT PON_identifier_UNIQUE UNIQUE (pon, identifier)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ');
            $connection->executeStatement('
                CREATE TABLE IF NOT EXISTS `recommendy_article_similarity`
                (
                    `id`         BINARY(16)   NOT NULL,
                    `pon`        VARCHAR(255) NOT NULL,
                    `son`        VARCHAR(255) NOT NULL,
                    `shop`       VARCHAR(255) NOT NULL,
                    `similarity` DECIMAL(5,4) NOT NULL,
                    `created_at` DATETIME(3)  NOT NULL,
                    `updated_at` DATETIME(3)  NULL,
                    CONSTRAINT PON_SON_UNIQUE UNIQUE (pon, son, shop)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ');
            $connection->executeStatement('
                CREATE TABLE IF NOT EXISTS `recommendy_tracking`
                (
                    `id`        BINARY(16)   NOT NULL,
                    `action`    INT          NOT NULL,
                    `pon`       VARCHAR(255) NOT NULL,
                    `price`     DOUBLE       NOT NULL,
                    `sessionId` VARCHAR(255) NOT NULL,
                    `created`   VARCHAR(255) NOT NULL,
                    `created_at` DATETIME(3) NOT NULL,
                    `updated_at` DATETIME(3) NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ');

            $connection->executeStatement('create index bundle_pon_shop_idx on recommendy_bundle_matrix (pon, shop)');
            $connection->executeStatement('create index bundle_son_shop_idx on recommendy_bundle_matrix (son, shop)');
            $connection->executeStatement('create index similarity_pon_shop_idx on recommendy_article_similarity (pon, shop)');
            $connection->executeStatement('create index similarity_son_shop_idx on recommendy_article_similarity (son, shop)');
            $connection->executeStatement('create index bundle_son_idx on recommendy_bundle_matrix (son);');
            $connection->executeStatement('create index bundle_pon_idx on recommendy_bundle_matrix (pon);');
            $connection->executeStatement('create index similarity_pon_idx on recommendy_article_similarity (pon);');
            $connection->executeStatement('create index similarity_son_idx on recommendy_article_similarity (son);');
            $connection->executeStatement('create index identifier_id_idx on recommendy_identifier (identifier);');

        } catch (Exception $e) {
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
