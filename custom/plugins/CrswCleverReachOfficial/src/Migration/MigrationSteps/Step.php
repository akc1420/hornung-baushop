<?php


namespace Crsw\CleverReachOfficial\Migration\MigrationSteps;

use Crsw\CleverReachOfficial\Migration\Exceptions\ApiCredentialsNotPresentException;
use Crsw\CleverReachOfficial\Migration\Exceptions\FailedToExecuteMigrationStepException;

/**
 * Class Step
 *
 * @package Crsw\CleverReachOfficial\Migration\MigrationSteps
 */
abstract class Step
{
    /**
     * Executes migration step.
     *
     * @throws ApiCredentialsNotPresentException
     * @throws FailedToExecuteMigrationStepException
     */
    abstract public function execute(): void;
}