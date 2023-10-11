<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks\Cache\Subtasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form;

class DeleteRemovedForms extends FormCacheUpdater
{
    const CLASS_NAME = __CLASS__;

    /**
     * @inheritDoc
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToRetrieveFormCacheException
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToDeleteFormCacheException
     */
    public function execute()
    {
        $forms = $this->getForms();

        $this->reportProgress(10);

        foreach ($this->getFromCacheService()->getForms() as $form) {
            if (!$this->isRemoved($form, $forms)) {
                continue;
            }

            $this->getFromCacheService()->deleteForm($form);
            $this->reportAlive();
        }

        $this->reportProgress(100);
    }

    /**
     * Checks if form has been removed on API.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form $form
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\DTO\Form[] $forms
     *
     * @return bool
     */
    private function isRemoved(Form $form, array $forms)
    {
        foreach ($forms as $formFromApi) {
            if ($formFromApi->getId() === $form->getApiId()) {
                return false;
            }
        }

        return true;
    }
}