<?php

namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Form;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\FormService as BaseFormService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

/**
 * Class FormService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Form
 */
class FormService extends BaseFormService
{
    /**
     * Retrieves the integration's default form name.
     *
     * @return string
     */
    public function getDefaultFormName(): string
    {
        return $this->getConfigService()->getIntegrationName();
    }

    /**
     * @return Configuration
     */
    private function getConfigService(): Configuration
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::class);
    }
}