<?php

declare(strict_types=1);

namespace salty\Sw6PerformanceAnalysis\Analyzer\Server;

use salty\Sw6PerformanceAnalysis\Analyzer\Analyzer;
use salty\Sw6PerformanceAnalysis\Struct\ResultCollection;

class Protocol extends Analyzer
{
    private const REQUIREMENTS = [
        'serverProtocol' => [
            'minVersion'     => '2',
            'suggestedValue' => '2',
        ],
    ];

    public function analyze(ResultCollection $collection): ResultCollection
    {
        $this->checkProtocolVersion($collection);

        return $collection;
    }

    private function checkProtocolVersion(ResultCollection $collection): void
    {
        $protocolVersion = $this->getProtocolVersion();
        $this->getResult($collection, 'serverProtocol', $protocolVersion, self::REQUIREMENTS);
    }

    private function getProtocolVersion(): string
    {
        $matches = [];

        preg_match_all('/\/(\d+)?.?(\d+)?.?/m', $_SERVER['SERVER_PROTOCOL'], $matches, PREG_SET_ORDER);

        unset($matches[0][0]);

        return implode('.', $matches[0]);
    }
}
