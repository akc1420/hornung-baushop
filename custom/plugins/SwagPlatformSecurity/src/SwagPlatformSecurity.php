<?php declare(strict_types=1);

namespace Swag\Security;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Kernel;
use Swag\Security\Components\RemoveDisabledServicesCompilerPass;
use Swag\Security\Components\State;
use Swag\Security\Components\UpdateHtaccess;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

require_once __DIR__ . '/Fixes/NEXT15858/init.php';
require_once __DIR__ . '/Fixes/NEXT21077/init.php';

class SwagPlatformSecurity extends Plugin
{
    public const PLUGIN_NAME = 'SwagPlatformSecurity';

    public function build(ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator([__DIR__ . '/Resources/config']));
        $loader->load('services.php');

        $this->fetchPluginConfig($container);
        $container->addCompilerPass(new RemoveDisabledServicesCompilerPass());
    }

    public function boot(): void
    {
        parent::boot();

        foreach ($this->container->getParameter('SwagPlatformSecurity.activeFixes') as $securityFix) {
            $securityFix::boot($this->container);
        }
    }

    public function install(InstallContext $installContext): void
    {
        $this->copyHtaccess();
    }

    public function update(UpdateContext $updateContext): void
    {
        $this->copyHtaccess();
    }

    private function fetchPluginConfig(ContainerBuilder $container): void
    {
        try {
            $qb = Kernel::getConnection()->createQueryBuilder();

            $config = $qb
                ->select(['ticket', 'active'])
                ->from('swag_security_config', 'config')
                ->execute()
                ->fetchAll(\PDO::FETCH_KEY_PAIR);

        } catch (\Throwable $e) {
            $config = [];
        }

        foreach ($config as &$item) {
            $item = (bool) $item;
        }
        unset($item);

        $shopwareVersion = $_SERVER['SHOPWARE_FAKE_VERSION'] ?? $container->getParameter('kernel.shopware_version');
        $availableFixes = [];
        $activeFixes = [];

        foreach (State::KNOWN_ISSUES as $knownIssue) {
            if (!$knownIssue::isValidForVersion($shopwareVersion)) {
                continue;
            }

            $availableFixes[] = $knownIssue;

            if (array_key_exists($knownIssue::getTicket(), $config) && !$config[$knownIssue::getTicket()]) {
                continue;
            }

            $knownIssue::buildContainer($container);

            $activeFixes[] = $knownIssue;
        }

        $container->setParameter('SwagPlatformSecurity.activeFixes', $activeFixes);
        $container->setParameter('SwagPlatformSecurity.availableFixes', $availableFixes);
    }

    private function copyHtaccess(): void
    {
        $projectDirHtaccess = $this->container->getParameter('kernel.project_dir'). '/.htaccess';

        copy(__DIR__ . '/../root_htaccess.dist', $projectDirHtaccess);

        $knownFolders = [
            $this->container->getParameter('kernel.project_dir'). '/bin',
            $this->container->getParameter('kernel.project_dir'). '/config',
            $this->container->getParameter('kernel.project_dir'). '/custom',
            $this->container->getParameter('kernel.project_dir'). '/files',
            $this->container->getParameter('kernel.project_dir'). '/src',
            $this->container->getParameter('kernel.project_dir'). '/var',
            $this->container->getParameter('kernel.project_dir'). '/vendor',
        ];

        foreach ($knownFolders as $knownFolder) {
            if (file_exists($knownFolder)) {
                copy(__DIR__ . '/../block_directory_access_htaccess.dist', $knownFolder . '/.htaccess');
            }
        }

        $shopwareVersion = $_SERVER['SHOPWARE_FAKE_VERSION'] ?? $this->container->getParameter('kernel.shopware_version');
        $updateService = new UpdateHtaccess();

        // Only update the .htaccess if we are on a older shopware version
        if (version_compare($shopwareVersion, $updateService->getMaxVersion(), '<')) {
            $publicHtaccess = $this->container->getParameter('kernel.project_dir'). '/public/.htaccess';
            $updateService->updateHtaccess($publicHtaccess, __DIR__ . '/../current_public_htaccess.dist');
        }
    }
}
