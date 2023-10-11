<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\API\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy as BaseProxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\HttpClient;

class Proxy extends BaseProxy
{
    const CLASS_NAME = __CLASS__;

    /**
     * Proxy constructor.
     *
     * @param \Crsw\CleverReachOfficial\Core\Infrastructure\Http\HttpClient $client
     */
    public function __construct(HttpClient $client)
    {
        parent::__construct($client, null);
    }

    /**
     * Checks if API is alive.
     *
     * @return bool
     */
    public function isAPIActive()
    {
        try {
            $this->get('debug/ping.json');
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function getHeaders()
    {
        return array();
    }
}