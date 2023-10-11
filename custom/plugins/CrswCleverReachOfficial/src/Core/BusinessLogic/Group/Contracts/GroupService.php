<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts;

interface GroupService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieves integration specific group name.
     *
     * @return string Integration provided group name.
     */
    public function getName();

    /**
     * Retrieves integration specific blacklisted emails suffix.
     *
     * @NOTICE SUFFIX MUST START WITH DASH (-)!
     *
     * @return string Blacklisted emails suffix.
     */
    public function getBlacklistedEmailsSuffix();

    /**
     * Retrieves group id.
     *
     * @return string Group id. Empty string if group id is not saved.
     */
    public function getId();

    /**
     * Saves group id.
     *
     * @param string $id Group id to be saved.
     *
     * @return void
     */
    public function setId($id);

    /**
     * Retrieves list of all groups for a current user.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Group\DTO\Group[] List of available groups.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getGroups();

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
    public function getGroupByName($name);

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
    public function createGroup($name);
}