<?php

namespace Crsw\CleverReachOfficial;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\DatabaseHandler;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http\TokenProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\Contracts\DynamicContentService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\Http\Proxy as DynamicContentProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\FormEventsService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\ReceiverEventsService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Uninstall\UninstallService;
use Crsw\CleverReachOfficial\Service\Infrastructure\LoggerService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

/**
 * Class CleverReach
 *
 * @package Crsw\CleverReachOfficial
 */
class CrswCleverReachOfficial extends Plugin
{

    /**
     * @inheritdoc
     * @param InstallContext $installContext
     */
    public function install(InstallContext $installContext): void
    {
        Bootstrap::init();
        $this->registerServices();
        parent::install($installContext);
    }

    /**
     * @inheritdoc
     * @param UpdateContext $context
     */
    public function update(UpdateContext $context): void
    {
        Bootstrap::init();
        $this->registerServices();
        ServiceRegister::registerService(
            EntityRepositoryInterface::class,
            function () {
                return $this->container->get('cleverreach_entity.repository');
            }
        );

        parent::update($context);
    }

    /**
     * Plugin uninstall method.
     *
     * @param UninstallContext $uninstallContext
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        Bootstrap::init();
        $this->registerServices();

        parent::uninstall($uninstallContext);

        $this->getUninstallService()->removeData();

        if (!$uninstallContext->keepUserData()) {
            $this->removeTable();
        }
    }

    /**
     * Removes CleverReach table.
     */
    private function removeTable(): void
    {
        try {
            /** @var Connection $connection */
            $connection = $this->container->get(Connection::class);
            $databaseHandler = new DatabaseHandler($connection);
            $databaseHandler->removeCleverReachTables();
        } catch (DBALException $e) {
            Logger::logError($e->getMessage());
        }
    }

    /**
     * @return UninstallService
     *
     * @noinspection PhpParamsInspection
     */
    private function getUninstallService(): UninstallService
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        $databaseHandler = new DatabaseHandler($connection);

        return new UninstallService(
            ServiceRegister::getService(GroupService::class),
            ServiceRegister::getService(FormEventsService::class),
            ServiceRegister::getService(ReceiverEventsService::class),
            ServiceRegister::getService(Proxy::class),
            ServiceRegister::getService(DynamicContentService::class),
            ServiceRegister::getService(DynamicContentProxy::class),
            ServiceRegister::getService(TokenProxy::class),
            $databaseHandler
        );
    }

    private function registerServices(): void
    {
        ServiceRegister::registerService(Connection::class, function () {
            return $this->container->get(Connection::class);
        });
        ServiceRegister::registerService(ShopLoggerAdapter::class, function () {
            return new LoggerService($this->container->get('kernel'));
        });
    }
}
