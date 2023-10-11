<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes) {
    $routes->import('../../Fixes/NEXT9689/MediaFileFetchRedirectTestController.php', 'annotation');
};
