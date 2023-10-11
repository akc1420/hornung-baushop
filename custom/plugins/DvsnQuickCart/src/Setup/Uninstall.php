<?php

/**
 * digitvision
 *
 * @category  digitvision
 * @package   Shopware\Plugins\DvsnQuickCart
 * @copyright (c) 2020 digitvision
 */

namespace Dvsn\QuickCart\Setup;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;

class Uninstall
{
    /**
     * Main bootstrap object.
     *
     * @var Plugin
     */
    private $plugin;

    /**
     * ...
     *
     * @var UninstallContext
     */
    private $context;

    /**
     * ...
     *
     * @var Connection
     */
    private $connection;

    /**
     * ...
     *
     * @var EntityRepositoryInterface
     */
    private $customFieldSetRepository;

    /**
     * ...
     *
     * @var EntityRepositoryInterface
     */
    private $customFieldRepository;

    /**
     * ...
     *
     * @param Plugin                    $plugin
     * @param UninstallContext          $context
     * @param Connection                $connection
     * @param EntityRepositoryInterface $customFieldSetRepository
     * @param EntityRepositoryInterface $customFieldRepository
     */
    public function __construct(Plugin $plugin, UninstallContext $context, Connection $connection, EntityRepositoryInterface $customFieldSetRepository, EntityRepositoryInterface $customFieldRepository)
    {
        // set params
        $this->plugin = $plugin;
        $this->context = $context;
        $this->connection = $connection;
        $this->customFieldSetRepository = $customFieldSetRepository;
        $this->customFieldRepository = $customFieldRepository;
    }

    /**
     * ...
     */
    public function uninstall(): void
    {
        // keep user data?
        if ($this->context->keepUserData()) {
            // dont remove anything
            return;
        }

        // clear plugin data
        $this->removeCustomFields();
        $this->removeDbTables();
    }

    /**
     * ...
     */
    private function removeCustomFields(): void
    {
        // remove every custom field
        foreach (DataHolder\CustomFields::$customFields as $customField) {
            /** @var CustomFieldSetEntity $customFieldSet */
            $customFieldSet = $this->customFieldSetRepository->search(
                (new Criteria())
                    ->addFilter(new EqualsFilter('custom_field_set.name', $customField['name'])),
                $this->context->getContext()
            )->first();

            // not found?
            if (!$customFieldSet instanceof CustomFieldSetEntity) {
                // ignore it
                continue;
            }

            // remove it
            $this->customFieldSetRepository->delete(
                [['id' => $customFieldSet->getId()]],
                $this->context->getContext()
            );
        }
    }

    /**
     * ...
     */
    private function removeDbTables(): void
    {
        // every table to trop
        $drop = implode(" \n ", array_map(
            function($table) {return 'DROP TABLE IF EXISTS `' . $table . '`;';},
            DataHolder\DbTables::$tables
        ));

        // remove every table
        $query = '
            SET FOREIGN_KEY_CHECKS=0;
            ' . $drop . '
            SET FOREIGN_KEY_CHECKS=1;
        ';

        // and execute
        $this->connection->executeQuery($query);

        // remove columns
        foreach (DataHolder\DbTables::$columns as $column) {
            // split it by table and column
            $split = explode('.', $column);

            // remove inheritance
            $query = 'ALTER TABLE `' . $split[0] . '` DROP COLUMN `' . $split[1] . '`;';

            // and execute
            try {
                $this->connection->executeQuery($query);
            }
            catch (DBALException $exception) {}
        }
    }
}
