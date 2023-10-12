<?php

namespace Deployer;

require_once 'recipe/common.php';
require_once 'contrib/cachetool.php';
  
set('cachetool', '/run/php/php-fpm.sock');
set('application', 'Shopware 6');
set('allow_anonymous_stats', false);
set('default_timeout', 3600); // Increase the `default_timeout`, if needed when tasks take longer than the limit.

set('repository', 'https://github.com/akc1420/hornung-staging.git');

host('k73g01.meinserver.io')
    ->setLabels([
        'type' => 'web',
        'env'  => 'production',
    ])
    ->setRemoteUser('c277968developement')
    ->set('deploy_path', '/var/www/clients/client1/web5/web/staging/')
    ->set('writable_mode', 'chmod')
    ->set('keep_releases', 3); // Keeps 3 old releases for rollbacks (if no DB migrations were executed) 
    
// For more information, please visit the Deployer docs: https://deployer.org/docs/configuration.html#shared_files
set('shared_files', [
    '.env',
    '.env.prod.local',
    'install.lock',
]);


// For more information, please visit the Deployer docs: https://deployer.org/docs/configuration.html#shared_dirs
set('shared_dirs', [
    'custom/plugins',
    'config/jwt',
    'files',
    'var/log',
    'public/media',
    'public/thumbnail',
    'public/sitemap',
]);

// For more information, please visit the Deployer docs: https://deployer.org/docs/configuration.html#writable_dirs
set('writable_dirs', [
    'custom/plugins',
    'config/jwt',
    'files',
    'public/bundles',
    'public/css',
    'public/fonts',
    'public/js',
    'public/media',
    'public/sitemap',
    'public/theme',
    'public/thumbnail',
    'var',
]);

// This task uploads the whole workspace to the target server
task('deploy:update_code', static function () {
    upload('.', '{{release_path}}');
});

// This task remotely creates the `install.lock` file on the target server.
task('sw:touch_install_lock', static function () {
    run('cd {{release_path}} && touch install.lock');
});

// This task remotely executes the `bin/build-js.sh` script on the target server.
// SHOPWARE_ADMIN_BUILD_ONLY_EXTENSIONS and DISABLE_ADMIN_COMPILATION_TYPECHECK make the build faster
// If you run into trouble with NPM it is recommended to add the .bashrc or .bash_aliases with source (for example when exporting NVM directory)
// task('sw:build', static function () {
//     run('cd {{release_path}} && source /var/www/vhosts/icreativetechnologies.de/.bashrc && export SHOPWARE_ADMIN_BUILD_ONLY_EXTENSIONS=1 && export DISABLE_ADMIN_COMPILATION_TYPECHECK=1 && bash bin/build-js.sh');
// });

// This task remotely executes the `theme:compile` console command on the target server.
task('sw:theme:compile', static function () {
    run('cd {{release_path}} && bin/console theme:compile');
});

// This task remotely executes the `cache:clear` console command on the target server.
task('sw:cache:clear', static function () {
    run('cd {{release_path}} && bin/console cache:clear');
});

// This task remotely executes the cache warmup console commands on the target server so that the first user, who
// visits the website doesn't have to wait for the cache to be built up.
task('sw:cache:warmup', static function () {
    run('cd {{release_path}} && bin/console cache:warmup');
    run('cd {{release_path}} && bin/console http:cache:warm:up');
});

// This task remotely executes the `database:migrate` console command on the target server.
// task('sw:database:migrate', static function () {
//     run('cd {{release_path}} && bin/console database:migrate --all');
// });

/**
 * Grouped SW deploy tasks
 */
task('sw:deploy', [
    'sw:touch_install_lock',
    // 'sw:database:migrate',
    // 'sw:build',
    'sw:cache:clear',
]);

/**
 * Main task
 */
task('deploy', [
    'deploy:prepare',
    'sw:deploy',
    'deploy:clear_paths',
    'deploy:publish',
])->desc('Deploy your project');


after('deploy:failed', 'deploy:unlock');
//after('deploy:symlink', 'cachetool:clear:opcache');

