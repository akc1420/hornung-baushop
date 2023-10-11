<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\TestBootstrapper;

if (is_readable(__DIR__ . '/../../../../src/Core/TestBootstrapper.php')) {
    require __DIR__ . '/../../../../src/Core/TestBootstrapper.php';
} else {
    require __DIR__ . '/../vendor/shopware/core/TestBootstrapper.php';
}

$classLoader = (new TestBootstrapper())
    ->setLoadEnvFile(true)
    ->setForceInstallPlugins(true)
    ->addCallingPlugin()
    ->bootstrap()
    ->getClassLoader();

$classLoader->addPsr4('Swag\\CmsExtensions\\Test\\', __DIR__);
