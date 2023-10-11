<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Contracts;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToCreateFormCacheException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToDeleteFormCacheException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToRetrieveFormCacheException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToUpdateFormCacheException;

interface FormCacheService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieves form identified by cache id.
     *
     * @param int $formId
     *
     * @return Form
     *
     * @throws FailedToRetrieveFormCacheException
     */
    public function getForm($formId);

    /**
     * Retrieves form identified by the API id.
     *
     * @param string $apiId
     *
     * @return Form
     *
     * @throws FailedToRetrieveFormCacheException
     */
    public function getFormByApiId($apiId);

    /**
     * Retrieves all cached forms.
     *
     * @return Form[]
     *
     * @throws FailedToRetrieveFormCacheException
     */
    public function getForms();

    /**
     * Creates form in cache.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form $form
     *
     * @return Form
     *
     * @throws FailedToCreateFormCacheException
     */
    public function createForm(Form $form);

    /**
     * Updates form in cache.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form $form
     *
     * @return Form
     *
     * @throws FailedToUpdateFormCacheException
     */
    public function updateForm(Form $form);

    /**
     * Deletes form in cache.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form $form
     *
     * @return void
     *
     * @throws FailedToDeleteFormCacheException
     */
    public function deleteForm(Form $form);
}