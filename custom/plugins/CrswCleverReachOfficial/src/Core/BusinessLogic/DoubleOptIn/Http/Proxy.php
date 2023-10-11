<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\DTO\DoubleOptInEmail;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\DTO\Transformers\SubmitTransformer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy as BaseProxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException;

/**
 * Class Proxy
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\Http
 */
class Proxy extends BaseProxy
{
    const CLASS_NAME = __CLASS__;

    /**
     * @param DoubleOptInEmail $email
     *
     * @throws FailedToRefreshAccessToken
     * @throws FailedToRetrieveAuthInfoException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function sendDoubleOptInEmail(DoubleOptInEmail $email)
    {
        $endpoint = "forms.json/{$email->getFormId()}/send/{$email->getType()}";

        $this->post($endpoint, SubmitTransformer::transform($email));
    }
}
