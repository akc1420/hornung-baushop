<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Form;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Contracts\FormCacheService as BaseService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Events\AfterFormCacheCreatedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Events\AfterFormCacheDeletedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Events\AfterFormCacheUpdatedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Events\BeforeFormCacheCreatedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Events\BeforeFormCacheDeletedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Events\BeforeFormCacheUpdatedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Events\FormEventBus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToCreateFormCacheException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToDeleteFormCacheException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToRetrieveFormCacheException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToUpdateFormCacheException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

/**
 * Class FormCacheService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Form
 */
class FormCacheService implements BaseService
{
    /**
     * @inheritDoc
     */
    public function getForm($formId)
    {
        try {
            $query = new QueryFilter();
            $query->where('context', Operators::EQUALS, $this->getConfigManager()->getContext());
            $query->where('id', Operators::EQUALS, $formId);

            $form = $this->getFormRepository()->selectOne($query);
        } catch (\Exception $e) {
            throw new FailedToRetrieveFormCacheException($e->getMessage(), $e->getCode());
        }

        if ($form === null) {
            throw new FailedToRetrieveFormCacheException("Form with id [$formId] not found in cache.");
        }

        return $form;
    }

    /**
     * @inheritDoc
     */
    public function getFormByApiId($apiId)
    {
        try {
            $query = new QueryFilter();
            $query->where('context', Operators::EQUALS, $this->getConfigManager()->getContext());
            $query->where('apiId', Operators::EQUALS, $apiId);

            $form = $this->getFormRepository()->selectOne($query);
        } catch (\Exception $e) {
            throw new FailedToRetrieveFormCacheException($e->getMessage(), $e->getCode());
        }

        if ($form === null) {
            throw new FailedToRetrieveFormCacheException("Form with id [$apiId] not found in cache.");
        }

        return $form;
    }

    /**
     * @inheritDoc
     */
    public function getForms()
    {
        try {
            $query = new QueryFilter();
            $query->where('context', Operators::EQUALS, $this->getConfigManager()->getContext());

            $forms = $this->getFormRepository()->select($query);
        } catch (\Exception $e) {
            throw new FailedToRetrieveFormCacheException($e->getMessage(), $e->getCode());
        }

        return $forms;
    }

    /**
     * @inheritDoc
     */
    public function createForm(Form $form)
    {
        FormEventBus::getInstance()->fire(new BeforeFormCacheCreatedEvent($form));

        try {
            $id = $this->getFormRepository()->save($form);
        } catch (\Exception $e) {
            throw new FailedToCreateFormCacheException($e->getMessage(), $e->getCode());
        }

        $form->setId($id);

        FormEventBus::getInstance()->fire(new AfterFormCacheCreatedEvent($form));

        return $form;
    }

    /**
     * @inheritDoc
     */
    public function updateForm(Form $form)
    {
        FormEventBus::getInstance()->fire(new BeforeFormCacheUpdatedEvent($form));

        try {
            $this->getFormRepository()->update($form);
        } catch (\Exception $e) {
            throw new FailedToUpdateFormCacheException($e->getMessage(), $e->getCode());
        }

        FormEventBus::getInstance()->fire(new AfterFormCacheUpdatedEvent($form));

        return $form;
    }

    /**
     * @inheritDoc
     */
    public function deleteForm(Form $form)
    {
        FormEventBus::getInstance()->fire(new BeforeFormCacheDeletedEvent($form));

        try {
            $this->getFormRepository()->delete($form);
        } catch (\Exception $e) {
            throw new FailedToDeleteFormCacheException($e->getMessage(), $e->getCode());
        }

        FormEventBus::getInstance()->fire(new AfterFormCacheDeletedEvent($form));
    }

    /**
     * Retrieves form repository.
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Interfaces\RepositoryInterface
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function getFormRepository()
    {
        return RepositoryRegistry::getRepository(Form::getClassName());
    }

    /**
     * Retrieves configuration manager.
     *
     * @return ConfigurationManager | object
     */
    private function getConfigManager()
    {
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME);
    }
}