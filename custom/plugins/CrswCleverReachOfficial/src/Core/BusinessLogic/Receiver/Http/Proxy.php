<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy as BaseProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Blacklist\Blacklist;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver\Transformers\SubmitTransformer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Blacklist\Transformers\SubmitTransformer as BlackListTransformer;

class Proxy extends BaseProxy
{
    /**
     * Class name.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Deletes receiver by email.
     *
     * @param string $email Receiver's email as an identifier.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function deleteReceiver($email)
    {
        $this->delete("receivers.json/$email");
    }

    /**
     * Removes receiver from a blacklist.
     *
     * @param string $email Receiver identifier.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function whitelist($email)
    {
        $email = urlencode($email);
        $this->delete("blacklist.json/$email");
    }

    /**
     * Adds receiver to blacklist.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Blacklist\Blacklist $blacklist object, with email and content
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function blacklist(Blacklist $blacklist)
    {
        $this->post('blacklist.json/', BlackListTransformer::transform($blacklist));
    }

    /**
     * Retrieves list of blacklisted emails for a given group.
     *
     * @return array List of retrieved blacklisted emails.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getBlacklisted()
    {
        $response = $this->get('blacklist.json');

        return array_map(function ($item) {return $item['email'];}, $response->decodeBodyToArray());
    }

    /**
     * Performs upsertplus action on the receivers endpoint.
     * For more details please
     * @see https://rest.cleverreach.com/explorer/v3#!/groups-v3/upsertplus_post
     *
     * @param $groupId
     * @param array $receivers
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function upsertPlus($groupId, array $receivers)
    {
        $this->post("groups.json/{$groupId}/receivers/upsertplus", SubmitTransformer::batchTransform($receivers));
    }

    /**
     * Retrieves receiver;
     *
     * @param string $groupId
     * @param string $receiverId
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver|\Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getReceiver($groupId, $receiverId)
    {
        $response = $this->get("groups.json/{$groupId}/receivers/{$receiverId}")->decodeBodyToArray();

        return Receiver::fromArray($response);
    }
}