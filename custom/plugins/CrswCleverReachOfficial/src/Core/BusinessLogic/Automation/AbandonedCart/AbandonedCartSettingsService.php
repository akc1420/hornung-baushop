<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts\AbandonedCartSettingsService as BaseService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartSettings;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

class AbandonedCartSettingsService implements BaseService
{
    /**
     * Persists settings.
     *
     * @param AbandonedCartSettings|null $settings
     *
     * @return void
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function set(AbandonedCartSettings $settings = null)
    {
        $data = $settings ? $settings->toArray() : null;

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->getConfigManager()->saveConfigValue('abandonedCartSettings', $data);
    }

    /**
     * Retrieves persisted settings.
     *
     * @return AbandonedCartSettings|null
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function get()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $settings = $this->getConfigManager()->getConfigValue('abandonedCartSettings');
        if ($settings !== null) {
            $settings = AbandonedCartSettings::fromArray($settings);
        }

        return $settings;
    }

    /**
     * @return ConfigurationManager | object
     */
    private function getConfigManager()
    {
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME);
    }
}