<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy as BaseProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\Event;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\EventRegisterResult;

/**
 * Class Proxy
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Http
 */
class Proxy extends BaseProxy
{
    const CLASS_NAME = __CLASS__;

    /**
     * Registers webhook event.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\Event $event
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\EventRegisterResult
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function registerEvent(Event $event)
    {
        $response = $this->post('hooks/eventhook', $event->toArray());

        return EventRegisterResult::fromArray($response->decodeBodyToArray());
    }

    /**
     * Deletes webhook event in a specific group with specific type.
     *
     * @param string $condition
     * @param string $type One of [form | receiver | automation]
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function deleteEvent($condition, $type)
    {
        $params = http_build_query(array('condition' => $condition));

        $this->delete("hooks/eventhook/$type?$params");
    }

    /**
     * Retrieves full request url.
     *
     * @param string $endpoint Endpoint identifier.
     *
     * @return string Full request url.
     */
    protected function getUrl($endpoint)
    {
        return self::BASE_API_URL . ltrim(trim($endpoint), '/');
    }
}