<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotDefinition;
use Shopware\Core\Content\MailTemplate\MailTemplateDefinition;
use Shopware\Core\Framework\Migration\MigrationStep;
use Swag\CmsExtensions\Form\Aggregate\FormGroup\FormGroupDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormGroupFieldTranslation\FormGroupFieldTranslationDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormGroupTranslation\FormGroupTranslationDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormTranslation\FormTranslationDefinition;
use Swag\CmsExtensions\Form\FormDefinition;

class Migration1608533666Form extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_608_533_666;
    }

    public function update(Connection $connection): void
    {
        $this->createFormTable($connection);
        $this->createFormTranslationTable($connection);
        $this->createFormGroupTable($connection);
        $this->createFormGroupTranslationTable($connection);
        $this->createFormGroupFieldTable($connection);
        $this->createFormGroupFieldTranslationTable($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function createFormTable(Connection $connection): void
    {
        $sql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS `#table#` (
                `id`                    BINARY(16)   NOT NULL,
                `#referenceTable1#_id`  BINARY(16)   NULL,
                `is_template`           TINYINT(1)   NOT NULL DEFAULT 0,
                `technical_name`        VARCHAR(255) NOT NULL,
                `#referenceTable2#_id`  BINARY(16)   NOT NULL,
                `created_at`            DATETIME(3)  NOT NULL,
                `updated_at`            DATETIME(3)  NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `uniq.swag_cms_extensions_form.#referenceTable1#_id`
                    UNIQUE (`#referenceTable1#_id`),
                CONSTRAINT `uniq.swag_cms_extensions_form.technical_name`
                    UNIQUE (`technical_name`),
                CONSTRAINT `fk.swag_cms_extensions_form.#referenceTable1#_id`
                    FOREIGN KEY (`#referenceTable1#_id`)
                    REFERENCES `#referenceTable1#` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                CONSTRAINT `fk.swag_cms_extensions_form.#referenceTable2#_id`
                    FOREIGN KEY (`#referenceTable2#_id`)
                    REFERENCES `#referenceTable2#` (`id`)
                    ON DELETE RESTRICT
                    ON UPDATE CASCADE
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement(\str_replace(
            ['#table#', '#referenceTable1#', '#referenceTable2#'],
            [FormDefinition::ENTITY_NAME, CmsSlotDefinition::ENTITY_NAME, MailTemplateDefinition::ENTITY_NAME],
            $sql
        ));
    }

    private function createFormTranslationTable(Connection $connection): void
    {
        $sql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS `#table#` (
                `#referenceTable#_id` BINARY(16)   NOT NULL,
                `language_id`         BINARY(16)   NOT NULL,
                `title`               VARCHAR(255) DEFAULT NULL,
                `success_message`     LONGTEXT     DEFAULT NULL,
                `receivers`           LONGTEXT     DEFAULT NULL,
                `created_at`          DATETIME(3)  NOT NULL,
                `updated_at`          DATETIME(3)  NULL,
                PRIMARY KEY (`language_id`, `#referenceTable#_id`),
                CONSTRAINT `fk.swag-cms-extensions-form.id`
                    FOREIGN KEY (`#referenceTable#_id`)
                    REFERENCES `#referenceTable#` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                CONSTRAINT `json.swag_cms_extensions_form_translations.receivers` CHECK(JSON_VALID(`receivers`))
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement(\str_replace(
            ['#table#', '#referenceTable#'],
            [FormTranslationDefinition::ENTITY_NAME, FormDefinition::ENTITY_NAME],
            $sql
        ));
    }

    private function createFormGroupTable(Connection $connection): void
    {
        $sql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS `#table#` (
                `id`                  BINARY(16)   NOT NULL,
                `#referenceTable#_id` BINARY(16)   NOT NULL,
                `position`            TINYINT(1)   NOT NULL,
                `technical_name`      VARCHAR(255) NOT NULL,
                `created_at`          DATETIME(3)  NOT NULL,
                `updated_at`          DATETIME(3)  NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.swag_cms_extensions_form_group.form_id`
                    FOREIGN KEY (`#referenceTable#_id`)
                    REFERENCES `#referenceTable#` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement(\str_replace(
            ['#table#', '#referenceTable#'],
            [FormGroupDefinition::ENTITY_NAME, FormDefinition::ENTITY_NAME],
            $sql
        ));
    }

    private function createFormGroupTranslationTable(Connection $connection): void
    {
        $sql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS `#table#` (
                `#referenceTable#_id` BINARY(16)   NOT NULL,
                `language_id`         BINARY(16)   NOT NULL,
                `title`               VARCHAR(255) DEFAULT NULL,
                `created_at`          DATETIME(3)  NOT NULL,
                `updated_at`          DATETIME(3)  NULL,
                PRIMARY KEY (`language_id`, `#referenceTable#_id`),
                CONSTRAINT `fk.swag_cms_extensions_form_group_translation.group_id`
                    FOREIGN KEY (`#referenceTable#_id`)
                    REFERENCES `#referenceTable#` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement(\str_replace(
            ['#table#', '#referenceTable#'],
            [FormGroupTranslationDefinition::ENTITY_NAME, FormGroupDefinition::ENTITY_NAME],
            $sql
        ));
    }

    private function createFormGroupFieldTable(Connection $connection): void
    {
        $sql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS `#table#` (
                `id`                  BINARY(16)   NOT NULL,
                `#referenceTable#_id` BINARY(16)   NOT NULL,
                `position`            TINYINT(1)   NOT NULL,
                `width`               TINYINT(1)   NOT NULL,
                `type`                VARCHAR(255) NOT NULL,
                `technical_name`      VARCHAR(255) NOT NULL,
                `required`            TINYINT(1)   NOT NULL,
                `created_at`          DATETIME(3)  NOT NULL,
                `updated_at`          DATETIME(3)  NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `uniq.swag_cms_extensions_form_group_field.form_name`
                    UNIQUE (`#referenceTable#_id`, `technical_name`),
                CONSTRAINT `fk.swag_cms_extensions_form_group_field.group_id`
                    FOREIGN KEY (`#referenceTable#_id`)
                    REFERENCES `#referenceTable#` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                CONSTRAINT `int.swag_cms_extensions_form_group_field.width` CHECK ((`width` BETWEEN 1 AND 12))
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement(\str_replace(
            ['#table#', '#referenceTable#'],
            [FormGroupFieldDefinition::ENTITY_NAME, FormGroupDefinition::ENTITY_NAME],
            $sql
        ));
    }

    private function createFormGroupFieldTranslationTable(Connection $connection): void
    {
        $sql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS `#table#` (
                `#referenceTable#_id` BINARY(16)   NOT NULL,
                `language_id`         BINARY(16)   NOT NULL,
                `label`               VARCHAR(255) DEFAULT NULL,
                `placeholder`         VARCHAR(255) DEFAULT NULL,
                `error_message`       LONGTEXT     DEFAULT NULL,
                `config`              LONGTEXT     DEFAULT NULL,
                `created_at`          DATETIME(3)  NOT NULL,
                `updated_at`          DATETIME(3)  NULL,
                PRIMARY KEY (`language_id`, `#referenceTable#_id`),
                CONSTRAINT `fk.cms_extensions_form_group_field_translation.group_field_id`
                    FOREIGN KEY (`#referenceTable#_id`)
                    REFERENCES `#referenceTable#` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                CONSTRAINT `json.swag_cms_extensions_form_group_field.config` CHECK(JSON_VALID(`config`))
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement(\str_replace(
            ['#table#', '#referenceTable#'],
            [FormGroupFieldTranslationDefinition::ENTITY_NAME, FormGroupFieldDefinition::ENTITY_NAME],
            $sql
        ));
    }
}
