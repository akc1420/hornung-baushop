<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Installation;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;

class CustomFieldSetInstaller
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function installCustomFieldSet(array $customFieldSet, array $relatedEntities = []): void
    {
        $technicalName = $customFieldSet['name'];
        $config = $customFieldSet['config'];
        $this->connection->executeStatement(
            'INSERT IGNORE INTO `custom_field_set`
                (id, name, config, active, created_at)
            VALUES
                (:id, :name, :config, 1, NOW(3))
            ON DUPLICATE KEY UPDATE
                `config` = VALUES(`config`),
                `updated_at` = NOW(3)',
            [
                'id' => md5($technicalName),
                'name' => $technicalName,
                'config' => json_encode($config),
            ],
        );

        $setId = $this->connection->fetchOne(
            'SELECT `id` FROM `custom_field_set` WHERE `name` = :name',
            ['name' => $technicalName],
        );

        $customFields = $customFieldSet['customFields'] ?? [];
        foreach ($customFields as $customField) {
            $this->installCustomField($customField, $setId);
        }

        foreach ($relatedEntities as $relatedEntity) {
            $this->installCustomFieldSetRelation($setId, $relatedEntity);
        }
    }

    private function installCustomField(array $customField, string $setId): void
    {
        if (!isset($customField['id'])) {
            $customField['id'] = Uuid::randomBytes();
        }

        if (!isset($customField['setId'])) {
            $customField['setId'] = $setId;
        }

        if (isset($customField['config']) &&  is_array($customField['config'])) {
            $customField['config'] = json_encode($customField['config'] ?? []);
        }

        $this->connection->executeStatement(
            'INSERT IGNORE INTO `custom_field`
                    (id, name, type, config, active, set_id, created_at)
                VALUES
                    (:id, :name, :type, :config, 1, :setId, NOW(3))
                ON DUPLICATE KEY UPDATE
                    `config` = VALUES(`config`),
                    `type` = VALUES(`type`),
                    `updated_at` = NOW(3)',
            $customField,
        );
    }

    private function installCustomFieldSetRelation(string $setId, string $relatedEntity): void
    {
        $this->connection->executeStatement(
            'INSERT INTO custom_field_set_relation
                (id, set_id, entity_name, created_at)
            VALUES
                (:id, :setId, :entityName, NOW(3))
            ON DUPLICATE KEY UPDATE
                `updated_at` = NOW(3)',
            [
                'id' => Uuid::randomBytes(),
                'setId' => $setId,
                'entityName' => $relatedEntity,
            ],
        );
    }
}
