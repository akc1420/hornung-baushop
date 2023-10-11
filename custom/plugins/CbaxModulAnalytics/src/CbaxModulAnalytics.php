<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;

use Cbax\ModulAnalytics\Bootstrap\Database;
use Cbax\ModulAnalytics\Bootstrap\DefaultConfig;

class CbaxModulAnalytics extends Plugin
{
    public function install(InstallContext $context): void
    {
        parent::install($context);
    }

    public function update(UpdateContext $context): void
    {
        $services = $this->getServices();
        $builder = new DefaultConfig();
        $builder->activate($services, $context->getContext());

        parent::update($context);
    }

    public function uninstall(UninstallContext $context): void
    {
        if ($context->keepUserData()) {
            parent::uninstall($context);

            return;
        }

        $services = $this->getServices();
        // Datenbank Tabellen löschen
        $db = new Database();
        $db->removeDatabaseTables($services);

        $this->removePluginConfig($services, $context->getContext());

        parent::uninstall($context);
    }

    public function activate(ActivateContext $context): void
    {
        $services = $this->getServices();
        $builder = new DefaultConfig();
        $builder->activate($services, $context->getContext());

        parent::activate($context);
    }

    public function deactivate(DeactivateContext $context): void
    {
        parent::deactivate($context);
    }

    private function removePluginConfig($services, $context)
    {
        $systemConfigRepository = $services['systemConfigRepository'];

        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('configurationKey', $this->getName() . '.config.'));
        $idSearchResult = $systemConfigRepository->searchIds($criteria, $context);

        $ids = array_map(static function ($id) {
            return ['id' => $id];
        }, $idSearchResult->getIds());

        if ($ids === []) {
            return;
        }

        $systemConfigRepository->delete($ids, $context);
    }

    private function getServices() {
        $services = array();

        /* Standard Services */
        $services['systemConfigService'] = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService');
        $services['systemConfigRepository'] = $this->container->get('system_config.repository');
        $services['stateMachineRepository'] = $this->container->get('state_machine.repository');
        $services['stateMachineStateRepository'] = $this->container->get('state_machine_state.repository');
        $services['connectionService'] =  $this->container->get(Connection::class);

        /* spezifische Services */

        return $services;
    }
}

