<?php

declare(strict_types=1);

namespace salty\Sw6PerformanceAnalysis\Analyzer\Server;

use salty\Sw6PerformanceAnalysis\Analyzer\Analyzer;
use salty\Sw6PerformanceAnalysis\Struct\ResultCollection;
use Throwable;

class Cache extends Analyzer
{
    private const REQUIREMENTS = [
        'apcu' => [
            'minValue'       => 1,
            'suggestedValue' => 1,
        ],
        'apcuMemory' => [
            'minValue'       => 128,
            'suggestedValue' => 128,
        ],
        'opcache' => [
            'minValue'       => 1,
            'suggestedValue' => 1,
        ],
        'opcacheMemory' => [
            'minValue'       => 256,
            'suggestedValue' => 256,
        ],
        'httpCache' => [
            'minValue'       => 1,
            'suggestedValue' => 1,
        ],
    ];

    public function analyze(ResultCollection $collection): ResultCollection
    {
        $this->checkApcuEnabled($collection);
        $this->checkOpcacheEnabled($collection);

        return $collection;
    }

    private function checkApcuEnabled(ResultCollection $collection): void
    {
        $isApcuEnabled = extension_loaded('apcu');
        $this->getResult($collection, 'apcu', (int) $isApcuEnabled, self::REQUIREMENTS);

        if ($isApcuEnabled !== true || defined('APC_ITER_MEM_SIZE') === false) {
            return;
        }

        $this->getResult($collection, 'apcuMemory', APC_ITER_MEM_SIZE, self::REQUIREMENTS);
    }

    private function checkOpcacheEnabled(ResultCollection $collection): void
    {
        $isOpcacheEnabled = extension_loaded('Zend OPcache') && ini_get('opcache.enable');
        $this->getResult($collection, 'opcache', (int) $isOpcacheEnabled, self::REQUIREMENTS);

        if ($isOpcacheEnabled !== true) {
            return;
        }

        try {
            $opcacheConfiguration = opcache_get_configuration();

            if($opcacheConfiguration === false) {
                return;
            }

            $opcacheMemory        = $opcacheConfiguration['directives']['opcache.memory_consumption'] / 1024 / 1024;
        } catch (Throwable $e) {
            return;
        }

        $this->getResult($collection, 'opcacheMemory', (int) $opcacheMemory, self::REQUIREMENTS);
    }
}
