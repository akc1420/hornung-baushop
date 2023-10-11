<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes) {
    $routes->import('../../Api/**/*.php', 'annotation');

    $routes->import('../../Fixes/NEXT15675/PrepareDownloadController.php', 'annotation');
};
