<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Installer;

use Doctrine\DBAL\Connection;
use Psr\Container\ContainerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use SwagSocialShopping\Exception\ExistingSocialShoppingSalesChannelsException;
use SwagSocialShopping\SwagSocialShopping;

class SalesChannelInstaller implements InstallerInterface
{
    private EntityRepositoryInterface $salesChannelRepository;

    private EntityRepositoryInterface $salesChannelTypeRepository;

    public function __construct(ContainerInterface $container)
    {
        $this->salesChannelRepository = $container->get('sales_channel.repository');
        $this->salesChannelTypeRepository = $container->get('sales_channel_type.repository');
        $this->connection = $container->get(Connection::class);
    }

    public function install(InstallContext $context): void
    {
    }

    public function update(UpdateContext $context): void
    {
    }

    public function uninstall(UninstallContext $context): void
    {
        $this->checkSocialShoppingSalesChannels($context->getContext());
        $this->removeSalesChannelType($context->getContext());

        if ($context->keepUserData()) {
            return;
        }

        $this->dropPluginTables();
    }

    public function activate(ActivateContext $context): void
    {
        $this->createSalesChannelType($context->getContext());
    }

    public function deactivate(DeactivateContext $context): void
    {
        $this->checkSocialShoppingSalesChannels($context->getContext());
        $this->removeSalesChannelType($context->getContext());
    }

    private function createSalesChannelType(Context $context): void
    {
        $this->salesChannelTypeRepository->upsert([
            [
                'id' => SwagSocialShopping::SALES_CHANNEL_TYPE_SOCIAL_SHOPPING,
                'iconName' => 'default-shopping-basket',
                'name' => 'Social Shopping',
                'manufacturer' => 'shopware AG',
                'description' => 'Empty container for social shopping networks',
                'translations' => [
                    'en-GB' => [
                        'name' => 'Social Shopping',
                        'manufacturer' => 'shopware AG',
                        'description' => 'Empty container for social shopping networks',
                    ],
                    'de-DE' => [
                        'name' => 'Social Shopping',
                        'manufacturer' => 'shopware AG',
                        'description' => 'Leerer Container für Social Shopping-Netzwerke',
                    ],
                ],
            ],
        ], $context);
    }

    /**
     * @throws ExistingSocialShoppingSalesChannelsException
     */
    private function checkSocialShoppingSalesChannels(Context $context): void
    {
        $criteria = new Criteria();
        $criteria
            ->addFilter(
                new EqualsFilter('typeId', SwagSocialShopping::SALES_CHANNEL_TYPE_SOCIAL_SHOPPING)
            );

        $result = $this->salesChannelRepository->search($criteria, $context);
        if ($result->getTotal() > 0) {
            $names = $result->getEntities()->map(function (SalesChannelEntity $item): string {
                return (string) $item->getName();
            });

            throw new ExistingSocialShoppingSalesChannelsException($result->getTotal(), $names);
        }
    }

    private function removeSalesChannelType(Context $context): void
    {
        $this->salesChannelTypeRepository->delete(
            [['id' => SwagSocialShopping::SALES_CHANNEL_TYPE_SOCIAL_SHOPPING]],
            $context
        );
    }

    private function dropPluginTables(): void
    {
        $this->connection->executeStatement('DROP TABLE IF EXISTS `swag_social_shopping_sales_channel`;');
        $this->connection->executeStatement('DROP TABLE IF EXISTS `swag_social_shopping_product_error`;');
        $this->connection->executeStatement('DROP TABLE IF EXISTS `swag_social_shopping_order`;');
        $this->connection->executeStatement('DROP TABLE IF EXISTS `swag_social_shopping_customer`;');
    }
}
