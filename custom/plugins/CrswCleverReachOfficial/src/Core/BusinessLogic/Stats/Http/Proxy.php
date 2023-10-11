<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy as BaseProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\DTO\Stats;

/**
 * Class Proxy
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Http
 */
class Proxy extends BaseProxy
{
    const CLASS_NAME = __CLASS__;

    /**
     * @param string $groupId
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\DTO\Stats
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getStats($groupId)
    {
        $response = $this->get("groups.json/$groupId/stats");

        return Stats::fromArray($response->decodeBodyToArray());
    }

    /**
     * Get the count of receivers with a certain tag
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag $tag
     * @param string|null $groupId
     *
     * @return string
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function countReceivers(Tag $tag, $groupId = null)
    {
        $endpoint = 'tags/count.json?' . $this->buildQuery($tag, $groupId);
        $response = $this->get($endpoint);

        return $response->getBody();
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag $tag
     * @param string|null $groupId
     *
     * @return string
     */
    private function buildQuery(Tag $tag, $groupId)
    {
        $queryParams = array(
            'tag' => (string)$tag,
        );

        if ($groupId) {
            $queryParams['group_id'] = $groupId;
        }

        return http_build_query($queryParams);
    }
}
