<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent;

use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Contracts\EventsService as BaseService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

/**
 * Class EventsService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent
 */
abstract class EventsService implements BaseService
{
    /**
     * @inheritDoc
     */
    public function getCallToken()
    {
        return $this->getValue('callToken', '');
    }

    /**
     * @inheritDoc
     */
    public function setCallToken($token)
    {
        $this->setValue('callToken', $token);
    }

    /**
     * @inheritDoc
     */
    public function getSecret()
    {
        return $this->getValue('secret', '');
    }

    /**
     * @inheritDoc
     */
    public function setSecret($secret)
    {
        $this->setValue('secret', $secret);
    }

    /**
     * @inheritDoc
     */
    public function getVerificationToken()
    {
        return $this->getValue('verificationToken', '');
    }

    /**
     * @inheritDoc
     */
    public function setVerificationToken($token)
    {
        $this->setValue('verificationToken', $token);
    }

    /**
     * Retrieves config value.
     *
     * @param string $key
     * @param mixed $default
     * @param bool $isContextSpecific
     *
     * @return mixed
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    protected function getValue($key, $default = null, $isContextSpecific = true)
    {
        $key = $this->getConfigKey($key);

        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->getConfigManager()->getConfigValue($key, $default, $isContextSpecific);
    }

    /**
     * Saves config value.
     *
     * @param string $key
     * @param mixed $value
     * @param bool $isContextSpecific
     *
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    protected function setValue($key, $value, $isContextSpecific = true)
    {
        $key = $this->getConfigKey($key);

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->getConfigManager()->saveConfigValue($key, $value, $isContextSpecific);
    }

    /**
     * Retrieves configuration manager.
     *
     * @return ConfigurationManager | object
     */
    private function getConfigManager()
    {
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME);
    }

    /**
     * Generates config key.
     *
     * @param $key
     *
     * @return string
     */
    private function getConfigKey($key)
    {
        $key = $this->getType() . '-' . $key;

        return $key;
    }
}