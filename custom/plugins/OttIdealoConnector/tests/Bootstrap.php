<?php declare(strict_types=1);

use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Symfony\Component\Dotenv\Dotenv;

if (isset($_SERVER['PROJECT_ROOT']) && file_exists($_SERVER['PROJECT_ROOT'])) {
    return $_SERVER['PROJECT_ROOT'];
}
if (isset($_ENV['PROJECT_ROOT']) && file_exists($_ENV['PROJECT_ROOT'])) {
    return $_ENV['PROJECT_ROOT'];
}

$rootDir = __DIR__;
$dir = $rootDir;
while (!file_exists($dir . '/.env')) {
    if ($dir === dirname($dir)) {
        return $rootDir;
    }
    $dir = dirname($dir);
}

define('TEST_PROJECT_DIR', $dir);

$loader = require TEST_PROJECT_DIR . '/vendor/autoload.php';
KernelLifecycleManager::prepare($loader);

if (!class_exists(Dotenv::class)) {
    throw new RuntimeException('APP_ENV environment variable is not defined. You need to define environment variables for configuration or add "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
}
(new Dotenv(true))->load(TEST_PROJECT_DIR . '/.env');

putenv('DATABASE_URL=' . getenv('DATABASE_URL') . '_test');
