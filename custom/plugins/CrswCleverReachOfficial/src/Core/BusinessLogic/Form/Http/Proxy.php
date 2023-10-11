<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\DTO\Form;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy as BaseProxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException;

class Proxy extends BaseProxy
{
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieves form identified form id.
     *
     * @param string $formId
     * @param bool $isContentIncluded
     *
     * @return Form
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getForm($formId, $isContentIncluded = false)
    {
        $formResponse = $this->get("forms.json/$formId")->decodeBodyToArray();

        if ($isContentIncluded) {
            $formResponse['content'] = $this->getContent($formId);
        }

        return Form::fromArray($formResponse);
    }

    /**
     * Retrieves forms that belong to a certain group.
     *
     * @param string $groupId
     * @param bool $isContentIncluded
     *
     * @return Form[]
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getForms($groupId, $isContentIncluded = false)
    {
        $formsResponse = $this->get("groups.json/$groupId/forms")->decodeBodyToArray();
        if ($isContentIncluded) {
            foreach ($formsResponse as $index => $form) {
                $formsResponse[$index]['content'] = $this->getContent($form['id']);
            }
        }

        return Form::fromBatch($formsResponse);
    }

    /**
     * Creates form.
     *
     * @link https://rest.cleverreach.com/explorer/v3#!/forms-v3/createTemplateForm_post
     *
     * @param string $groupId
     * @param string $type
     * @param array $typeData
     *
     * @return string
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function createForm($groupId, $type, array $typeData)
    {
        $response = $this->post("forms.json/$groupId/createfromtemplate/$type", $typeData)->decodeBodyToArray();

        if (empty($response['id'])) {
            throw new HttpRequestException(
                'Failed to create form.',
                400
            );
        }

        return $response['id'];
    }

    /**
     * Retrieves form content.
     *
     * @param string $formId
     *
     * @return string
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    private function getContent($formId)
    {
        $response = $this->get("forms.json/$formId/code?badget=false&embedded=true");

        /** @noinspection JsonEncodingApiUsageInspection */
        return json_decode($response->getBody());
    }
}