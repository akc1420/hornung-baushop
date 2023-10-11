<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use SwagSocialShopping\Installer\CustomFieldInstaller;
use SwagSocialShopping\Installer\SalesChannelInstaller;

class SwagSocialShopping extends Plugin
{
    public const SALES_CHANNEL_TYPE_SOCIAL_SHOPPING = '9ce0868f406d47d98cfe4b281e62f098';
    public const SOCIAL_SHOPPING_SALES_CHANNEL_WRITTEN_EVENT = 'swag_social_shopping_sales_channel.written';

    private const SWAG_SOCIAL_SHOPPING_SALES_CHANNEL_PRIVILEGE_KEY = 'swag_social_shopping_sales_channel:';
    private const SWAG_SOCIAL_SHOPPING_PRODUCT_ERROR_PRIVILEGE_KEY = 'swag_social_shopping_product_error:';

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);

        /** @var EntityRepositoryInterface $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        (new CustomFieldInstaller($customFieldSetRepository))->activate($activateContext);
        (new SalesChannelInstaller($this->container))->activate($activateContext);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);

        /** @var EntityRepositoryInterface $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        (new CustomFieldInstaller($customFieldSetRepository))->deactivate($deactivateContext);
        (new SalesChannelInstaller($this->container))->deactivate($deactivateContext);
    }

    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);

        /** @var EntityRepositoryInterface $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        (new CustomFieldInstaller($customFieldSetRepository))->install($installContext);
        (new SalesChannelInstaller($this->container))->install($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        /** @var EntityRepositoryInterface $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        (new CustomFieldInstaller($customFieldSetRepository))->uninstall($uninstallContext);
        (new SalesChannelInstaller($this->container))->uninstall($uninstallContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);

        /** @var EntityRepositoryInterface $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        (new CustomFieldInstaller($customFieldSetRepository))->update($updateContext);
        (new SalesChannelInstaller($this->container))->update($updateContext);
    }

    public function enrichPrivileges(): array
    {
        return [
            'sales_channel.viewer' => [
                self::SWAG_SOCIAL_SHOPPING_SALES_CHANNEL_PRIVILEGE_KEY . 'read',
                self::SWAG_SOCIAL_SHOPPING_PRODUCT_ERROR_PRIVILEGE_KEY . 'read',
                'seo_url:read',
                'product_visibility:read',
            ],
            'sales_channel.editor' => [
                self::SWAG_SOCIAL_SHOPPING_SALES_CHANNEL_PRIVILEGE_KEY . 'update',
            ],
            'sales_channel.creator' => [
                self::SWAG_SOCIAL_SHOPPING_SALES_CHANNEL_PRIVILEGE_KEY . 'create',
            ],
        ];
    }
}
