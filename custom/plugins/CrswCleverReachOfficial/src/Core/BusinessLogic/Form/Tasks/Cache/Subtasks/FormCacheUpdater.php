<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks\Cache\Subtasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Contracts\FormCacheService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\DTO\Form;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

abstract class FormCacheUpdater extends Task
{
    /**
     * @var callable $formsProvider
     */
    protected $formsProvider;

    /**
     * Sets a function that will provide current forms that must be cached.
     *
     * @param callable $formsProvider
     */
    public function setFormsProvider(callable $formsProvider)
    {
        $this->formsProvider = $formsProvider;
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\DTO\Form[]
     */
    protected function getForms()
    {
        return call_user_func($this->formsProvider);
    }

    /**
     * @return FormCacheService | object
     */
    protected function getFromCacheService()
    {
        return ServiceRegister::getService(FormCacheService::CLASS_NAME);
    }

    /**
     * @return ConfigurationManager | object
     */
    protected function getConfigManager()
    {
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME);
    }

    /**
     * Translate dto to entity.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\DTO\Form $dto
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form
     */
    protected function translateDtoToEntity(Form $dto)
    {
        $entity = new \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form();
        $entity->setApiId($dto->getId());
        $entity->setContent($dto->getContent());
        $entity->setContext($this->getConfigManager()->getContext());
        $entity->setCustomerTableId($dto->getCustomerTableId());
        $entity->setName($dto->getName());

        return $entity;
    }
}