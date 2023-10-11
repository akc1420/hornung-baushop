<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Survey;

use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

/**
 * Class SurveyStorageService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Survey
 */
abstract class SurveyStorageService implements Contracts\SurveyStorageService
{
    /**
     * @param string $type
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigEntity
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function setSurveyOpened($type)
    {
        return $this->getConfigurationManager()->saveConfigValue($type . 'FormOpened', true);
    }

    /**
     * @param string $type
     *
     * @return bool
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function isSurveyOpened($type)
    {
        return (bool)$this->getConfigurationManager()->getConfigValue($type . 'FormOpened', false);
    }

    /**
     * Returns last poll ID retrieved from CleverReach poll endpoint.
     *
     * @return string|null Poll ID
     * @throws QueryFilterInvalidParamException
     */
    public function getLastPollId()
    {
        return $this->getConfigurationManager()->getConfigValue('lastPollId');
    }

    /**
     * Sets last poll ID retrieved from CleverReach poll endpoint.
     *
     * @param string $pollId Poll ID
     *
     * @throws QueryFilterInvalidParamException
     */
    public function setLastPollId($pollId)
    {
        $this->getConfigurationManager()->saveConfigValue('lastPollId', $pollId);
    }

    /**
     * Retrieves configuration manager.
     *
     * @return ConfigurationManager Configuration Manager instance.
     */
    protected function getConfigurationManager()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME);
    }
}