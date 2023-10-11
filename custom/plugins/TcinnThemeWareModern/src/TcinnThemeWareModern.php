<?php declare(strict_types=1);

namespace TcinnThemeWareModern;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Storefront\Framework\ThemeInterface;
use TcinnThemeWareModern\CustomFields\CustomFieldUpdater;
/* ThemeWare: Handle cms media services */
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class TcinnThemeWareModern extends Plugin implements ThemeInterface
{
    // ToDo: Remove for apps in deployment

    private function getCustomFieldUpdater()
    {
        /**
         * @var EntityRepositoryInterface $customFieldSetRepository
         */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        /**
         * @var EntityRepositoryInterface $customFieldRepository
         */
        $customFieldRepository = $this->container->get('custom_field.repository');

        return new CustomFieldUpdater(
            $customFieldSetRepository,
            $customFieldRepository,
            $this->path
        );
    }

    public function getThemeConfigPath(): string
    {
        return 'theme.json';
    }

    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);

        $this->getCustomFieldUpdater()->sync();
    }

    /**
     * Copy preview media from main theme to child themes.
     *
     * @param UpdateContext $updateContext
     */
    public function postUpdate(UpdateContext $updateContext): void
    {
        /** @var EntityRepository $themeRepository */
        $themeRepository = $this->container->get('theme.repository');

        $parentThemeCollection = $themeRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('technicalName', 'TcinnThemeWareModern')),
            \Shopware\Core\Framework\Context::createDefaultContext()
        );

        if(!$parentThemeCollection) {
            return;
        }

        $parentTheme = $parentThemeCollection->first();

        $childThemesCollection = $themeRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('parentThemeId', $parentTheme->get('id'))),
            \Shopware\Core\Framework\Context::createDefaultContext()
        );

        if(!$childThemesCollection) {
            return;
        }

        foreach ($childThemesCollection->getElements() as $childTheme) {
            if(!$childTheme->get('previewMediaId')) {
                $data = [
                    [
                        'id' => $childTheme->get('id'),
                        'previewMediaId' => $parentTheme->get('previewMediaId')
                    ]
                ];
            } else {
                $data = [
                    [
                        'id' => $childTheme->get('id')
                    ]
                ];
            }
            $themeRepository->update($data, \Shopware\Core\Framework\Context::createDefaultContext());
        }
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if (!$uninstallContext->keepUserData()) {
            $this->getCustomFieldUpdater()->remove();
        }
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);

        $this->getCustomFieldUpdater()->sync();
    }

    /**
     * Skip rebuild container on activate/deactivate process
     * to speedup Shopware Cloud bundle integration.
     *
     * @return bool
     */
    public function rebuildContainer(): bool
    {
        return false;
    }

    /**
     * Load media.xml to add cms data resolver services
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Core/Content/DependencyInjection'));
        $loader->load('media.xml');
    }
}
