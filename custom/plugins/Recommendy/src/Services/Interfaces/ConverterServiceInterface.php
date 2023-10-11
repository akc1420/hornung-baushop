<?php

namespace Recommendy\Services\Interfaces;

use Exception;
use Recommendy\Components\Struct\ConfigStruct;

interface ConverterServiceInterface
{
    /**
     * @param array $config
     * @return ConfigStruct
     * @throws Exception
     */
    public function convertConfig(array $config): ConfigStruct;
}
