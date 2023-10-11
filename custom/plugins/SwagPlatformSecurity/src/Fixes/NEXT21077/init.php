<?php

// The fix contains this class
use Composer\Autoload\ClassLoader;

try {
    $reflection = new \ReflectionClass(\Dompdf\Dompdf::class);
    $versionFile = realpath(dirname($reflection->getFileName()) . '/../VERSION');

    if (file_exists($versionFile) && ($version = trim(file_get_contents($versionFile))) !== false && $version !== '$Format:<%h>$') {
        if (version_compare($version, '1.2.1') > 0 || version_compare($version, '1.0.3') === 0) {
            // Fix is already included in versions higher than 1.2.1, or in our custom patched 1.0.3 version
            return;
        }
    }
} catch (\ReflectionException $e) {
    // DomPDF is not installed, nothing to fix
    return;
}

/** @var ClassLoader $c */
global $classLoader, $loader;
$classLoader instanceof ClassLoader && $classLoader->addClassMap(['Dompdf\FontMetrics' => __DIR__ .'/FontMetrics.php']);
$loader instanceof ClassLoader && $loader->addClassMap(['Dompdf\FontMetrics' => __DIR__ .'/FontMetrics.php']);

require_once __DIR__ . '/FontMetrics.php';
