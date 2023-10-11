<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent;

use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\Contracts\DynamicContentService as BaseService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

/**
 * Class DynamicContentService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent
 */
abstract class DynamicContentService implements BaseService
{
    /**
     * Appends created content id to the list
     *
     * @param string $id
     *
     * @throws QueryFilterInvalidParamException
     */
    public function addCreatedDynamicContentId($id)
    {
        $existingIds = $this->getCreatedDynamicContentIds();
        if (!in_array($id, $existingIds, true)) {
            $existingIds[] = $id;
            $this->getConfigurationManager()->saveConfigValue('dynamicContentIds', json_encode($existingIds));
        }
    }

    /**
     * Returns list of created content ids
     *
     * @return string[]
     *
     * @throws QueryFilterInvalidParamException
     */
    public function getCreatedDynamicContentIds()
    {
        $encodedIds = $this->getConfigurationManager()->getConfigValue('dynamicContentIds');
        $ids = json_decode($encodedIds, true);

        return is_array($ids) ? $ids : array();
    }

    /**
     * @return string|null
     * @throws QueryFilterInvalidParamException
     */
    public function getDynamicContentPassword()
    {
        $password = $this->getConfigurationManager()->getConfigValue('dynamicContentPassword');

        return !empty($password) ? $password : $this->createDynamicContentPassword();
    }

    /**
     * @throws QueryFilterInvalidParamException
     */
    public function createDynamicContentPassword()
    {
        $password = hash('md5', time());
        $this->getConfigurationManager()->saveConfigValue('dynamicContentPassword', $password);

        return $password;
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
