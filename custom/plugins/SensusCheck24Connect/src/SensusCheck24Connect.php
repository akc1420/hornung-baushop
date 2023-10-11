<?php declare(strict_types=1);

namespace Sensus\Check24Connect;

use Sensus\Check24Connect\Utils\Installer;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class SensusCheck24Connect extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        $context = $installContext->getContext();

        $systemConfigService = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService');
        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $this->container->get('sales_channel.repository');

        $installer = new Installer($systemConfigService, $salesChannelRepository);
        $installer->install($context);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $context = $uninstallContext->getContext();

        $this->removeCreatedEntities($context);
        $this->removeConfiguration($context);
    }

    private function removeCreatedEntities(Context $context): void
    {
        $systemConfigService = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService');

        /** @var array|null $entityMap */
        $entityMap = $systemConfigService->get(Installer::SYSTEM_CONFIG_KEY);

        foreach ($entityMap as $entityName => $entityIds) {
            /** @var EntityRepositoryInterface $repository */
            $repository = $this->container->get($entityName . '.repository');

            $ids = array_map(function ($id) {
                return ['id' => $id];
            }, $entityIds);
            $repository->delete($ids, $context);
        }
    }

    private function removeConfiguration(Context $context): void
    {
        /** @var EntityRepositoryInterface $systemConfigRepository */
        $systemConfigRepository = $this->container->get('system_config.repository');
        $criteria = (new Criteria())->addFilter(new ContainsFilter('configurationKey', $this->getName() . '.config.'));
        $idSearchResult = $systemConfigRepository->searchIds($criteria, $context);

        $ids = array_map(static function ($id) {
            return ['id' => $id];
        }, $idSearchResult->getIds());

        if ($ids === []) {
            return;
        }

        $systemConfigRepository->delete($ids, $context);
    }
}