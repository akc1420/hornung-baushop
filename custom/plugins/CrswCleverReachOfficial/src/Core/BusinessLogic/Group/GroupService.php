<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Group;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService as BaseService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\DTO\Group;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\TimeProvider;

abstract class GroupService implements BaseService
{
    /**
     * Group proxy.
     *
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Http\Proxy
     */
    protected $proxy;

    /**
     * Retrieves group id.
     *
     * @return string Group id. Empty string if group id is not saved.
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getId()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->getConfigurationManager()->getConfigValue('groupId', '');
    }

    /**
     * Saves group id.
     *
     * @param string $id Group id to be saved.
     *
     * @return void
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function setId($id)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->getConfigurationManager()->saveConfigValue('groupId', $id);
    }

    /**
     * Retrieves list of available groups for the current user.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Group\DTO\Group[] List of available groups.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getGroups()
    {
        return $this->getProxy()->getGroups();
    }

    /**
     * Retrieves group by name.
     *
     * @param string $name
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Group\DTO\Group | null
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getGroupByName($name)
    {
        $result = null;

        if (empty($name)) {
            return null;
        }

        $groups = $this->getGroups();
        foreach ($groups as $group) {
            if ($group->getName() === $name) {
                $result = $group;

                break;
            }
        }

        return $result;
    }

    /**
     * Creates group with provided name.
     *
     * @param string $name Group name.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Group\DTO\Group Created group.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function createGroup($name)
    {
        $group = new Group();
        $group->setName($name);
        $group->setBackup(true);
        $group->setLocked(true);
        $time = $this->getTimeProvider()->getDateTime(time())->format(DATE_ATOM);
        $integrationName = $this->getConfigurationService()->getIntegrationName();
        $clientId = $this->getConfigurationService()->getClientId();
        $url = $this->getConfigurationService()->getSystemUrl();
        $group->setReceiverInfo("[$time] Automatically created by $integrationName $clientId ($url).");

        return $this->getProxy()->createGroup($group);
    }
    /**
     * Retrieves configuration manager.
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager Configuration Manager instance.
     */
    protected function getConfigurationManager()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME);
    }

    /**
     * Retrieves group proxy.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Http\Proxy Group proxy instance.
     */
    protected function getProxy()
    {
        if ($this->proxy === null) {
            $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }

    /**
     * Retrieves time provider.
     *
     * @return TimeProvider | object Time Provider instance.
     */
    protected function getTimeProvider()
    {
        return ServiceRegister::getService(TimeProvider::CLASS_NAME);
    }

    /**
     * Retrieves configuration service.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Configuration\Configuration | object Config service instance.
     */
    protected function getConfigurationService()
    {
        return ServiceRegister::getService(Configuration::CLASS_NAME);
    }
}