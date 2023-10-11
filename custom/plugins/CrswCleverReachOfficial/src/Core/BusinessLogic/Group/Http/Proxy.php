<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\DTO\Group;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Transformers\SubmitTransformer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy as BaseProxy;

class Proxy extends BaseProxy
{
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieves groups for current user.
     *
     * @return Group[] List of available groups.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getGroups()
    {
        $response = $this->get('groups.json');

        return Group::fromBatch($response->decodeBodyToArray());
    }

    /**
     * Creates group with a given name.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Group\DTO\Group $group
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Group\DTO\Group Created group instance.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function createGroup(Group $group)
    {
        $response = $this->post('groups.json', SubmitTransformer::transform($group));

        return Group::fromArray($response->decodeBodyToArray());
    }
}