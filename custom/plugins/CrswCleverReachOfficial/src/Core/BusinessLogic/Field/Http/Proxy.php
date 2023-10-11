<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Field\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Field\DTO\Field;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Field\DTO\Field\Transformers\SubmitTransformer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy as BaseProxy;

class Proxy extends BaseProxy
{
    /**
     * Class name.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieves list of global fields.
     *
     * @return Field[]
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getGlobalFields()
    {
        $response = $this->get('attributes.json');

        return Field::fromBatch($response->decodeBodyToArray());
    }

    /**
     * Updates global field.
     *
     * @param string $id Field id.
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Field\DTO\Field $field Field updated data.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Field\DTO\Field
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function updateField($id, Field $field)
    {
        $response = $this->put("attributes.json/{$id}", SubmitTransformer::transform($field));

        return Field::fromArray($response->decodeBodyToArray());
    }

    /**
     * Creates global field.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Field\DTO\Field $field Global field data.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Field\DTO\Field
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function createField(Field $field)
    {
        $response = $this->post('attributes.json', SubmitTransformer::transform($field));

        return Field::fromArray($response->decodeBodyToArray());
    }
}