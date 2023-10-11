<?php

namespace Recommendy\Services\Interfaces;

use Recommendy\Components\Struct\ConfigStruct;

interface ConfigServiceInterface
{
    /**
     * @param string|null $salesChannelId
     * @return array|false
     */
    public function getConfigs(string $salesChannelId = null);

    /**
     * @param string $name
     * @param null $default
     * @param string|null $salesChannelId
     * @return mixed|null
     */
    public function getConfig(string $name, $default = null, string $salesChannelId = null);

    /**
     * @param string|null $salesChannelId
     * @return ConfigStruct
     */
    public function getConfigStruct(?string $salesChannelId = null): ConfigStruct;
}
