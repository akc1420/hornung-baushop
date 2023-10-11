<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveUserInfoException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy as BaseProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO\PollAnswer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO\Survey;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

/**
 * Class Proxy
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\Http
 */
class Proxy extends BaseProxy
{
    const CLASS_NAME = __CLASS__;

    const USER_ID = 1;
    const POLL_ENDPOINT = 'poll';

    /**
     * Returns available poll if exists.
     *
     * @param string $type Survey type.
     * @param string $lang Language in which the survey should be displayed.
     *
     * @return Survey
     */
    public function getSurvey($type, $lang)
    {
        $response = $this->get('xss?' . $this->buildQuery($type, $lang));

        return Survey::fromArray($response->decodeBodyToArray());
    }

    /**
     * Responds to a poll.
     *
     * @param string $token Token retrieved on requesting poll.
     * @param PollAnswer $answer Request body.
     *
     * @return int
     */
    public function submitAnswer($token, PollAnswer $answer)
    {
        try {
            return $this->post("xss?token=$token", $answer->toArray())->getStatus();
        } catch (\Exception $exception) {
            return $exception->getCode();
        }
    }

    /**
     * Ignores a poll.
     *
     * @param string $token Token retrieved on requesting poll.
     * @param string $pollId Poll ID.
     * @param string|null $customerId Customer ID.
     *
     * @return int
     */
    public function ignore($token, $pollId, $customerId = null)
    {
        $uri = "xss/$pollId/ignore";
        if (!empty($customerId)) {
            $uri .= "/$customerId";
        }

        $uri .= "?token=$token";

        try {
            return $this->post($uri)->getStatus();
        } catch (\Exception $exception) {
            return $exception->getCode();
        }
    }

    /**
     * @inheritDoc
     * @param string $endpoint
     *
     * @return string
     */
    protected function getUrl($endpoint)
    {
        return self::BASE_API_URL . self::POLL_ENDPOINT  . '/' . ltrim(trim($endpoint), '/');
    }

    /**
     * @inheritDoc
     * @return array
     */
    protected function getHeaders()
    {
        return $this->getBaseHeaders();
    }

    /**
     * Build url query parameters
     *
     * @param string $type
     * @param string $lang
     *
     * @return string
     */
    protected function buildQuery($type, $lang)
    {
        $queryParams = array(
            'user_id' => static::USER_ID,
            'url' => '/' . strtolower(ServiceRegister::getService(Configuration::CLASS_NAME)->getIntegrationName()) . '/' . $type,
            'lang' => $lang,
        );

        try {
            $queryParams['customer_id'] = $this->authService->getUserInfo()->getId();
        } catch (FailedToRetrieveUserInfoException $exception) {
            // if user info doesn't exits, just skip customer_id parameter
        }

        return http_build_query($queryParams);
    }
}
