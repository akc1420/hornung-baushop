<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeDefinition;
use Shopware\Core\Content\MailTemplate\MailTemplateDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Swag\CmsExtensions\Util\Feature\SwagCmsExtensionsFeatureUtil;
use Swag\CmsExtensions\Util\Lifecycle\FormDefaults;
use Swag\CmsExtensions\Util\Lifecycle\Uninstaller;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SwagCmsExtensions extends Plugin
{
    private const SWAG_CMS_EXTENSIONS_QUICKVIEW_PRIVILEGE_KEY = 'swag_cms_extensions_quickview:';
    private const SWAG_CMS_EXTENSIONS_SCROLL_NAVIGATION_PRIVILEGE_KEY = 'swag_cms_extensions_scroll_navigation:';
    private const SWAG_CMS_EXTENSIONS_SCROLL_NAVIGATION_PAGE_SETTINGS_PRIVILEGE_KEY = 'swag_cms_extensions_scroll_navigation_page_settings:';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $featureUtil = new SwagCmsExtensionsFeatureUtil();
        $featureUtil->registerFeatures();

        $featureLoader = new XmlFileLoader($container, new FileLocator($this->getPath() . '/Resources/config/Features/'));

        $featureLoader->load('form_builder.xml');
        $featureLoader->load('quickview.xml');
        $featureLoader->load('block_rule.xml');
        $featureLoader->load('scroll_navigation.xml');
    }

    public function boot(): void
    {
        parent::boot();

        $featureUtil = new SwagCmsExtensionsFeatureUtil();
        $featureUtil->registerFeatures();
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        (new Uninstaller(
            $uninstallContext,
            $connection
        ))->uninstall();
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);

        $this->getFormDefaults()->update($updateContext);
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);

        $this->getFormDefaults()->activate($activateContext->getContext());
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);

        $this->getFormDefaults()->deactivate($deactivateContext->getContext());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function enrichPrivileges(): array
    {
        return [
            'cms.viewer' => [
                self::SWAG_CMS_EXTENSIONS_QUICKVIEW_PRIVILEGE_KEY . 'read',
                self::SWAG_CMS_EXTENSIONS_SCROLL_NAVIGATION_PRIVILEGE_KEY . 'read',
                self::SWAG_CMS_EXTENSIONS_SCROLL_NAVIGATION_PAGE_SETTINGS_PRIVILEGE_KEY . 'read',
            ],
            'cms.editor' => [
                self::SWAG_CMS_EXTENSIONS_QUICKVIEW_PRIVILEGE_KEY . 'update',
                self::SWAG_CMS_EXTENSIONS_SCROLL_NAVIGATION_PRIVILEGE_KEY . 'update',
                self::SWAG_CMS_EXTENSIONS_SCROLL_NAVIGATION_PAGE_SETTINGS_PRIVILEGE_KEY . 'update',
            ],
            'cms.creator' => [
                self::SWAG_CMS_EXTENSIONS_QUICKVIEW_PRIVILEGE_KEY . 'create',
                self::SWAG_CMS_EXTENSIONS_SCROLL_NAVIGATION_PRIVILEGE_KEY . 'create',
                self::SWAG_CMS_EXTENSIONS_SCROLL_NAVIGATION_PAGE_SETTINGS_PRIVILEGE_KEY . 'create',
            ],
            'cms.deleter' => [
                self::SWAG_CMS_EXTENSIONS_QUICKVIEW_PRIVILEGE_KEY . 'delete',
                self::SWAG_CMS_EXTENSIONS_SCROLL_NAVIGATION_PRIVILEGE_KEY . 'delete',
                self::SWAG_CMS_EXTENSIONS_SCROLL_NAVIGATION_PAGE_SETTINGS_PRIVILEGE_KEY . 'delete',
            ],
        ];
    }

    private function getFormDefaults(): FormDefaults
    {
        /** @var EntityRepository $mailTemplateRepository */
        $mailTemplateRepository = $this->container->get(\sprintf('%s.repository', MailTemplateDefinition::ENTITY_NAME));
        /** @var EntityRepository $mailTemplateTypeRepository */
        $mailTemplateTypeRepository = $this->container->get(\sprintf('%s.repository', MailTemplateTypeDefinition::ENTITY_NAME));

        return new FormDefaults($mailTemplateRepository, $mailTemplateTypeRepository);
    }
}
