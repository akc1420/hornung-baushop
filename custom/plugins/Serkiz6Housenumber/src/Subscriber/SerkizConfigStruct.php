<?php declare(strict_types=1);


namespace Serkiz6Housenumber\Subscriber;

use Shopware\Core\Framework\Struct\Struct;

class SerkizConfigStruct extends Struct
{

    protected $config = [];

    public $list = [];

    protected $facebookPurchaseCode = null;

    /**
     * @var string
     */
    private $configPath = 'Serkiz6Housenumber.config.';

    public function __construct($configData)
    {
        foreach($configData as $key=>$v){
            $this->setValues($configData,str_replace($this->configPath,'',$key));
        }
    }

    public function getConfig() : array
    {
        return $this->config;
    }

    public function setList($list){
        $this->list = $list;
    }

    public function getList($list){
        return $this->list;
    }
 
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function setValues(array $pluginConfig, string $key, $default = null): void
    {
        if(array_key_exists($this->configPath . $key, $pluginConfig)) {
            $this->config[$key] = $pluginConfig[$this->configPath . $key];
        }
        else {
            $this->config[$key] = $default;
        }
    }
}
