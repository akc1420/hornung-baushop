<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Field\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Field\Contracts\FieldService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Field\DTO\Field;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Field\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

class CreateFieldsTask extends Task
{
    const CLASS_NAME = __CLASS__;
    /**
     * Field proxy.
     *
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Field\Http\Proxy
     */
    protected $proxy;
    /**
     * Field service.
     *
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Field\Contracts\FieldService
     */
    protected $service;

    /**
     * Creates or updates receiver fields.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function execute()
    {
        $proxy = $this->getProxy();
        $fields = $proxy->getGlobalFields();
        $this->reportProgress(20);

        $map = $this->createHashMap($fields);
        $this->reportProgress(30);

        $integrationFields = $this->getService()->getFields();
        foreach ($integrationFields as $integrationField) {
            if (array_key_exists($integrationField->getName(), $map)) {
                $updatedField = $map[$integrationField->getName()];
                $proxy->updateField($updatedField->getId(), $integrationField);
            } else {
                $proxy->createField($integrationField);
            }
        }

        $this->reportProgress(100);
    }

    /**
     * Transforms list of fields to hash map where elements are identified by field name.
     *
     * @param Field[] $fields
     *
     * @return Field[]
     */
    protected function createHashMap(array $fields)
    {
        $result = array();

        foreach ($fields as $field) {
            $result[$field->getName()] = $field;
        }

        return $result;
    }

    /**
     * Retrieves Field proxy.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Field\Http\Proxy
     */
    protected function getProxy()
    {
        if ($this->proxy === null) {
            $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }

    /**
     * Retrieves FieldService;
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Field\Contracts\FieldService
     */
    protected function getService()
    {
        if ($this->service === null) {
            $this->service = ServiceRegister::getService(FieldService::CLASS_NAME);
        }

        return $this->service;
    }
}