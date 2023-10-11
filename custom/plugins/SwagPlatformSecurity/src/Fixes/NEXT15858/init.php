<?php

// The fix contains this class
use Composer\Autoload\ClassLoader;

if (class_exists('League\Flysystem\CorruptedPathDetected') || class_exists('League\Flysystem\Util', false)) {
    return;
}

/** @var ClassLoader $c */
global $classLoader, $loader;
$classLoader instanceof ClassLoader && $classLoader->addClassMap(['League\Flysystem\Util' => __DIR__ .'/Util.php']);
$loader instanceof ClassLoader && $loader->addClassMap(['League\Flysystem\Util' => __DIR__ .'/Util.php']);

require_once __DIR__ . '/Util.php';
