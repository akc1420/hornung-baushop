<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Contracts\FormService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\DTO\Form;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

/**
 * Class CreateDefaultFormTask
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks
 */
class CreateDefaultFormTask extends Task
{
    const CLASS_NAME = __CLASS__;

    /**
     * Creates default form for the integration if the form does not exist.
     *
     * Form name is used to identify if the default form is already created or not.
     *
     * Saves default form id locally.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToRetriveFormException
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToCreateFormException
     */
    public function execute()
    {
        $groupId = $this->getGroupService()->getId();
        $formService = $this->getFormService();
        $formName = $formService->getDefaultFormName();
        $forms = $formService->getForms($groupId);

        $this->reportProgress(40);

        $id = null;
        foreach ($forms as $form) {
            if ($this->isDefaultForm($form, $formName, $groupId)) {
                $id = $form->getId();
                break;
            }
        }

        if ($id === null) {
            $id = $formService->createForm($groupId, 'default', array('name' => $formName, 'title' => $formName));
        }

        $this->getFormService()->setId($id);

        $this->reportProgress(100);
    }

    /**
     * Retrieves group service.
     *
     * @return GroupService | object
     */
    private function getGroupService()
    {
        return ServiceRegister::getService(GroupService::CLASS_NAME);
    }

    /**
     * Retrieves form service.
     *
     * @return FormService | object
     */
    private function getFormService()
    {
        return ServiceRegister::getService(FormService::CLASS_NAME);
    }

    /**
     * Checks if form is the default form.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\DTO\Form $form
     *
     * @param string $formName
     * @param string $groupId
     *
     * @return bool
     */
    private function isDefaultForm(Form $form, $formName, $groupId)
    {
        return $form->getName() === $formName && $form->getCustomerTableId() === $groupId;
    }
}