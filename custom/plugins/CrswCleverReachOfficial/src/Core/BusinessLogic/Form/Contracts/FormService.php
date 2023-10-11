<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Contracts;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\DTO\Form;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToCreateFormException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToRetriveFormException;

/**
 * Interface FormService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Contracts
 */
interface FormService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieves the integration's default form name.
     *
     * @return string
     */
    public function getDefaultFormName();

    /**
     * Sets default form id.
     *
     * @param string $id
     *
     * @return void
     */
    public function setId($id);

    /**
     * Retrieves default form id.
     *
     * @return string
     */
    public function getId();

    /**
     * Retrieve form identified by the form id.
     *
     * @param string $formId
     * @param bool $isContentIncluded
     *
     * @return Form
     *
     * @throws FailedToRetriveFormException
     */
    public function getForm($formId, $isContentIncluded = false);

    /**
     * Retrieve all forms belonging to a certain group.
     *
     * @param string $groupId
     * @param bool $isContentIncluded
     *
     * @return Form[]
     *
     * @throws FailedToRetriveFormException
     */
    public function getForms($groupId, $isContentIncluded = false);

    /**
     * Create form based on a template identified by a type.
     *
     * @param string $groupId
     * @param string $type
     * @param array $typeData
     *
     * @return string Returns created form id.
     *
     * @throws FailedToCreateFormException
     */
    public function createForm($groupId, $type, array $typeData);
}