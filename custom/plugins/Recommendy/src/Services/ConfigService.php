<?php

namespace Recommendy\Services;

use Exception;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Recommendy\Components\Struct\ConfigStruct;
use Recommendy\Services\Interfaces\ConfigServiceInterface;
use Recommendy\Services\Interfaces\ConverterServiceInterface;

class ConfigService implements ConfigServiceInterface
{
    /**
     * @var ConverterServiceInterface $converter
     */
    private $converter;

    /**
     * @var string
     */
    private $pluginName;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @param ConverterServiceInterface $converter
     * @param string $pluginName
     * @param SystemConfigService $systemConfigService
     */
    public function __construct(
        ConverterServiceInterface $converter,
        string                    $pluginName,
        SystemConfigService       $systemConfigService
    )
    {
        $this->pluginName = $pluginName;
        $this->converter = $converter;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @param string|null $salesChannelId
     * @return array|false
     */
    public function getConfigs(string $salesChannelId = null)
    {
        $domain = $this->systemConfigService->getDomain($this->pluginName, $salesChannelId, true);

        $keys = array_map(function ($key) {
            return lcfirst(str_replace($this->pluginName.'.config.'.ConfigStruct::PREFIX, '', $key));
        }, array_keys($domain));

        return array_combine($keys, array_values($domain));
    }

    /**
     * @param string $name
     * @param null $default
     * @param string|null $salesChannelId
     * @return mixed|null
     */
    public function getConfig(string $name, $default = null, string $salesChannelId = null)
    {
        if (strpos($name, ConfigStruct::PREFIX) === 0) {
            $name = substr($name, strlen(ConfigStruct::PREFIX));
        }
        $configs = $this->getConfigs($salesChannelId);
        return $configs[$name] ?? $default;
    }

    /**
     * @param string|null $salesChannelId
     * @return ConfigStruct
     * @throws Exception
     */
    public function getConfigStruct(string $salesChannelId = null): ConfigStruct
    {
        return $this->converter->convertConfig($this->getConfigs($salesChannelId));
    }
}
