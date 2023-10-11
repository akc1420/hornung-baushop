<?php

namespace Crsw\CleverReachOfficial\Service\Infrastructure;

use Crsw\CleverReachOfficial\Core\Infrastructure\Http\CurlHttpClient;

/**
 * Class Http1Client
 *
 * Client that enforces HTTP 1.1 protocol for its requests.
 *
 * @package App\Http\Client
 */
class Http1Client extends CurlHttpClient
{
    protected function setCurlOptions()
    {
        $this->curlOptions[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;

        parent::setCurlOptions();
    }
}
