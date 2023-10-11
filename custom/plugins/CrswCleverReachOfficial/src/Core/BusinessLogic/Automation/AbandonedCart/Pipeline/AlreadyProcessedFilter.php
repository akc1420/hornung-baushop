<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Pipeline;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTriggeredLog;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToPassFilterException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

class AlreadyProcessedFilter extends Filter
{
    /**
     * Checks whether trigger can pass the filter.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger $trigger
     *
     * @return void
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToPassFilterException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function pass(AbandonedCartTrigger $trigger)
    {
        $query = new QueryFilter();
        $query->where('cartId', Operators::EQUALS, $trigger->getCartId());
        $query->where('context', Operators::EQUALS, $this->getConfigManager()->getContext());
        $log = $this->getLogRepository()->selectOne($query);
        if ($log !== null) {
            throw new FailedToPassFilterException("Cart [{$trigger->getCartId()}] has already been triggered.");
        }
    }

    /**
     * Retrieves log repository.
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Interfaces\RepositoryInterface
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function getLogRepository()
    {
        return RepositoryRegistry::getRepository(AbandonedCartTriggeredLog::getClassName());
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