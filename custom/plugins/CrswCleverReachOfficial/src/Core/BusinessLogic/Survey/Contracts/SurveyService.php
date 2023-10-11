<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\Contracts;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO\PollAnswer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO\Survey;

/**
 * Interface SurveyService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\Contracts
 */
interface SurveyService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Returns required type of survey if exits
     *
     * @param string $type Survey type {plugin_installed, initial_sync_finished, first_form_used, periodic}
     * @param string $lang Language in which the survey should be displayed.
     *
     * @return Survey|null
     */
    public function getSurvey($type, $lang);

    /**
     * Submits an answer to the CleverReach Poll API.
     *
     * @param string $token Token retrieved on requesting poll.
     * @param PollAnswer $pollAnswer
     *
     * @return mixed
     */
    public function submitAnswer($token, PollAnswer $pollAnswer);

    /**
     * Ignores survey form on CleverReach API.
     *
     * @param string $token Token retrieved on requesting poll.
     * @param string $pollId Poll ID
     * @param string $customerId Customer ID
     *
     * @return mixed
     */
    public function ignorePoll($token, $pollId, $customerId);
}
