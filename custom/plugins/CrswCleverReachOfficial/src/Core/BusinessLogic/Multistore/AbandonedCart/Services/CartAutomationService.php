<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Services;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\CartAutomation;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToCreateCartException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToDeleteCartException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateCartException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\CartAutomationService as BaseService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Create\CreateCartAutomationTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueService;

/**
 * Class CartAutomationService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Services
 */
class CartAutomationService implements BaseService
{
    /**
     * Creates cart automation.
     *
     * @param string $storeId
     * @param string $name
     * @param string $source
     * @param array $settings
     *
     * @return CartAutomation
     *
     * @throws FailedToCreateCartException
     */
    public function create($storeId, $name, $source, array $settings)
    {
        $cart = new CartAutomation();
        $cart->setStoreId($storeId);
        $cart->setName($name);
        $cart->setSource($source);
        $cart->setSettings($settings);
        $cart->setContext($this->getCurrentContext());
        $cart->setStatus('initialized');
        try {
            $id = $this->getRepository()->save($cart);
            $cart->setId($id);
            $this->enqueue(new CreateCartAutomationTask($id));
        } catch (\Exception $e) {
            throw  new FailedToCreateCartException($e->getMessage(), $e->getCode(), $e);
        }

        return $cart;
    }

    /**
     * Updates cart.
     *
     * @param CartAutomation $cart
     *
     * @return CartAutomation
     *
     * @throws FailedToUpdateCartException
     */
    public function update(CartAutomation $cart)
    {
        try {
            $this->getRepository()->update($cart);
        } catch (\Exception $e) {
            throw new FailedToUpdateCartException($e->getMessage(), $e->getCode(), $e);
        }

        return $cart;
    }

    /**
     * Provides cart automation identified by id.
     *
     * @param int $id
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\CartAutomation | null
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function find($id)
    {
        $query = new QueryFilter();
        $query->where('id', Operators::EQUALS, $id);
        $query->where('context', Operators::EQUALS, $this->getCurrentContext());

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getRepository()->selectOne($query);
    }

    /**
     * Provides carts identified by query.
     *
     * @param array $query
     *
     * @return CartAutomation[]
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function findBy(array $query)
    {
        $query['context'] = $this->getCurrentContext();
        $queryFilter = new QueryFilter();

        foreach ($query as $column => $value) {
            if ($value === null) {
                $queryFilter->where($column, Operators::NULL);
            } else {
                $queryFilter->where($column, Operators::EQUALS, $value);
            }
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getRepository()->select($queryFilter);
    }

    /**
     * Deletes cart identified by id.
     *
     * @param int $id
     *
     * @return void
     *
     * @throws FailedToDeleteCartException
     */
    public function delete($id)
    {
        try {
            if ($cart = $this->find($id)) {
                $this->getRepository()->delete($cart);
            }
        } catch (\Exception $e) {
            throw new FailedToDeleteCartException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Deletes carts identified by query.
     *
     * @param array $query
     *
     * @return void
     *
     * @throws FailedToDeleteCartException
     */
    public function deleteBy(array $query)
    {
        try {
            $carts = $this->findBy($query);
            $repository = $this->getRepository();
            foreach ($carts as $cart) {
                $repository->delete($cart);
            }
        } catch (\Exception $e) {
            throw new FailedToDeleteCartException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Provides cart automation repository.
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Interfaces\RepositoryInterface
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getRepository()
    {
        return RepositoryRegistry::getRepository(CartAutomation::getClassName());
    }

    /**
     * Provides context.
     *
     * @return string
     */
    protected function getCurrentContext()
    {
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME)->getContext();
    }

    /**
     * Enqueues cart automation task.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Create\CreateCartAutomationTask $task
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    protected function enqueue(CreateCartAutomationTask $task)
    {
        /** @var QueueService $queue */
        $queue = ServiceRegister::getService(QueueService::CLASS_NAME);
        /** @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Configuration\Configuration $configuration */
        $configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        $queue->enqueue($configuration->getDefaultQueueName(), $task, $this->getCurrentContext());
    }
}