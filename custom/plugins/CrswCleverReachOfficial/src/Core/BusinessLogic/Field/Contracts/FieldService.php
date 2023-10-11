<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Field\Contracts;

/**
 * Interface FieldService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Field\Contracts
 */
interface FieldService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieve list of fields that an integration supports.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Field\DTO\Field[]
     */
    public function getFields();
}