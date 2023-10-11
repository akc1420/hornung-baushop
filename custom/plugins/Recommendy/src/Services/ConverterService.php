<?php

namespace Recommendy\Services;

use Exception;
use Recommendy\Components\Struct\ConfigStruct;
use Recommendy\Services\Interfaces\ConverterServiceInterface;

class ConverterService implements ConverterServiceInterface
{
    /**
     * @param array $config
     * @return ConfigStruct
     * @throws Exception
     */
    public function convertConfig(array $config): ConfigStruct
    {
        if (empty($config)) {
            throw new Exception('Config data is empty.');
        }
        return new ConfigStruct($config);
    }
}
