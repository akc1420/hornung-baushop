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
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;

class Install
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
     * @var InstallContext
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
     * @param InstallContext            $context
     * @param Connection                $connection
     * @param EntityRepositoryInterface $customFieldSetRepository
     * @param EntityRepositoryInterface $customFieldRepository
     */
    public function __construct(Plugin $plugin, InstallContext $context, Connection $connection, EntityRepositoryInterface $customFieldSetRepository, EntityRepositoryInterface $customFieldRepository)
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
    public function install(): void
    {
        // create custom fields
        foreach (DataHolder\CustomFields::$customFields as $customField) {
            // and save it and ignore exceptions
            try {
                $this->customFieldSetRepository->upsert(
                    [$customField],
                    $this->context->getContext()
                );
            }
            catch (\Exception $exception) {}
        }
    }
}
