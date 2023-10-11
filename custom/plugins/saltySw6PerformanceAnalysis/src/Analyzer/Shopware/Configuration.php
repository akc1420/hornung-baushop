<?php

declare(strict_types=1);

namespace salty\Sw6PerformanceAnalysis\Analyzer\Shopware;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use salty\Sw6PerformanceAnalysis\Analyzer\Analyzer;
use salty\Sw6PerformanceAnalysis\Struct\ResultCollection;
use Throwable;

class Configuration extends Analyzer
{
    private const REQUIREMENTS = [
        'kernelEnvironment' => [
            'expected'       => 'prod',
            'suggestedValue' => 'prod',
        ],
        'externalStorage' => [
            'suggestedValue' => 'External',
            'isNot'          => 'Local',
        ],
        'elasticSearch' => [
            'suggestedValue' => 1,
            'minValue'       => 1,
        ],
        'httpCache' => [
            'minValue'       => 1,
            'suggestedValue' => 1,
        ],
    ];

    /** @var string */
    private $kernelEnvironment;

    /** @var FilesystemInterface */
    private $fileSystem;

    /** @var bool */
    private $elasticSearchEnabled;

    /** @var bool */
    private $httpCacheEnabled;

    public function __construct(
        string $kernelEnvironment,
        FilesystemInterface $fileSystem,
        bool $elasticSearchEnabled,
        bool $httpCacheEnabled
   ) {
        $this->kernelEnvironment    = $kernelEnvironment;
        $this->fileSystem           = $fileSystem;
        $this->elasticSearchEnabled = $elasticSearchEnabled;
        $this->httpCacheEnabled     = $httpCacheEnabled;
    }

    public function analyze(ResultCollection $collection): ResultCollection
    {
        $this->checkEnvironmentMode($collection);
        $this->checkHttpCacheEnabled($collection);
        $this->checkFileSystem($collection);
        $this->checkElasticSearch($collection);
        //$this->checkSessionHandler($collection);

        return $collection;
    }

    private function checkHttpCacheEnabled(ResultCollection $collection): void
    {
        $this->getResult($collection, 'httpCache', $this->httpCacheEnabled, self::REQUIREMENTS);
    }

    private function checkEnvironmentMode(ResultCollection $collection): void
    {
        $this->getResult($collection, 'kernelEnvironment', $this->kernelEnvironment, self::REQUIREMENTS);
    }

    private function checkFileSystem(ResultCollection $collection): void
    {
        try {
            /** @var Filesystem $fileSystem */
            $fileSystem  = $this->fileSystem;
            $adapter     = explode('\\', get_class($fileSystem->getAdapter()));
            $adapterName = end($adapter);
        } catch (Throwable $e) {
            return;
        }

        $this->getResult($collection, 'externalStorage', $adapterName, self::REQUIREMENTS);
    }

    private function checkElasticSearch(ResultCollection $collection): void
    {
        $this->getResult($collection, 'elasticSearch', $this->elasticSearchEnabled, self::REQUIREMENTS);
    }

    private function checkSessionHandler(ResultCollection $collection): void
    {
        //TODO: get session handler
    }
}
