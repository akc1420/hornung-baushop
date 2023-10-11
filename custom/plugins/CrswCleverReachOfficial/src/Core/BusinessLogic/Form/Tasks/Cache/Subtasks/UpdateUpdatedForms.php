<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks\Cache\Subtasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form;

class UpdateUpdatedForms extends FormCacheUpdater
{
    const CLASS_NAME = __CLASS__;

    /**
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToRetrieveFormCacheException
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToUpdateFormCacheException
     */
    public function execute()
    {
        $forms = $this->getForms();

        $this->reportProgress(10);

        foreach ($this->getFromCacheService()->getForms() as $form) {
            if (($formDto = $this->getFormDto($form, $forms)) !== null) {
                $this->update($form, $formDto);
            }

            $this->reportAlive();
        }

        $this->reportProgress(100);
    }

    /**
     * Retrieves dto for specified entity.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form $form
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\DTO\Form[] $forms
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\DTO\Form | null
     */
    private function getFormDto(Form $form, array $forms)
    {
        foreach ($forms as $formFromApi) {
            if ($formFromApi->getId() === $form->getApiId()) {
                return $formFromApi;
            }
        }

        return null;
    }

    /**
     * Updates form.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form $form
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\DTO\Form $formDto
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToUpdateFormCacheException
     */
    private function update(Form $form, \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\DTO\Form $formDto)
    {
        $entity = $this->translateDtoToEntity($formDto);
        $entity->setId($form->getId());

        $this->getFromCacheService()->updateForm($entity);
    }
}