<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\DynamicContent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Transformers\SubmitTransformer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\Exceptions\ContentWithSameNameExistsException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy as BaseProxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException;

/**
 * Class Proxy
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\Http
 */
class Proxy extends BaseProxy
{
    const CLASS_NAME = __CLASS__;
    /**
     * Returns all dynamic contents from mycontent endpoint
     *
     * @return DynamicContent[]
     *
     * @throws FailedToRefreshAccessToken
     * @throws FailedToRetrieveAuthInfoException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function fetchAll()
    {
        $response = $this->get('mycontent.json');

        return DynamicContent::fromBatch($response->decodeBodyToArray());
    }

    /**
     * Creates new dynamic content
     *
     * @param DynamicContent $content
     *
     * @return DynamicContent created content
     *
     * @throws ContentWithSameNameExistsException
     * @throws \Exception
     */
    public function create(DynamicContent $content)
    {
        try {
            $response = $this->post('mycontent.json', SubmitTransformer::transform($content));
        } catch (\Exception $exception) {
            if ($exception->getCode() === 409) {
                throw new ContentWithSameNameExistsException($exception->getMessage(), 409);
            }

            throw $exception;
        }

        return DynamicContent::fromArray($response->decodeBodyToArray());
    }

    /**
     * Updates existing content
     *
     * @param string $id
     * @param DynamicContent $content
     *
     * @return DynamicContent updated content
     *
     * @throws FailedToRefreshAccessToken
     * @throws FailedToRetrieveAuthInfoException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function update($id, DynamicContent $content)
    {
        $response = $this->put("mycontent.json/$id", SubmitTransformer::transform($content));

        return DynamicContent::fromArray($response->decodeBodyToArray());
    }

    /**
     * Deletes dynamic content identified by the id.
     *
     * @param string $id
     *
     * @return void
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function deleteContent($id)
    {
        $this->delete("mycontent.json/$id");
    }
}
