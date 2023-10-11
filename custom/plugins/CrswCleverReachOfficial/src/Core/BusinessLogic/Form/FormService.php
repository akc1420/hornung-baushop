<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Form;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Contracts\FormService as BaseService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToCreateFormException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToRetriveFormException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

abstract class FormService implements BaseService
{
    /**
     * @inheritDoc
     */
    public function setId($id)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->getConfigManager()->saveConfigValue('defaultFormId', $id);
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->getConfigManager()->getConfigValue('defaultFormId', '');
    }

    /**
     * @inheritDoc
     */
    public function getForm($formId, $isContentIncluded = false)
    {
        try {
            $form = $this->getProxy()->getForm($formId, $isContentIncluded);
        } catch (\Exception $e) {
            throw new FailedToRetriveFormException($e->getMessage(), $e->getCode());
        }

        return $form;
    }

    /**
     * @inheritDoc
     */
    public function getForms($groupId, $isContentIncluded = false)
    {
        try {
            $forms = $this->getProxy()->getForms($groupId, $isContentIncluded);
        } catch (\Exception $e) {
            throw new FailedToRetriveFormException($e->getMessage(), $e->getCode());
        }

        return $forms;
    }

    /**
     * @inheritDoc
     */
    public function createForm($groupId, $type, array $typeData)
    {
        try {
            $id = $this->getProxy()->createForm($groupId, $type, $typeData);
        } catch (\Exception $e) {
            throw new FailedToCreateFormException($e->getMessage(), $e->getCode());
        }

        return $id;
    }

    /**
     * Retrieves form proxy.
     *
     * @return Proxy | object
     */
    private function getProxy()
    {
        return ServiceRegister::getService(Proxy::CLASS_NAME);
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