<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Group;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\GroupService as BaseGroupService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

/**
 * Class GroupService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Group
 */
class GroupService extends BaseGroupService
{
    /**
     * Retrieves integration specific group name.
     *
     * @return string Integration provided group name.
     */
    public function getName(): string
    {
        return $this->getConfigService()->getIntegrationName();
    }

    /**
     * Retrieves integration specific blacklisted emails suffix.
     *
     * @return string Blacklisted emails suffix.
     */
    public function getBlacklistedEmailsSuffix(): string
    {
        return '-Shopware-6';
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