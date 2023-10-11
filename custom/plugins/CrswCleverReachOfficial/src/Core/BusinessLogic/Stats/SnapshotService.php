<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Stats;

use Crsw\CleverReachOfficial\Core\BusinessLogic\ORM\Interfaces\ConditionallyDeletes;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Entity\Stats;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

/**
 * Class SnapshotService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Stats
 */
class SnapshotService implements Contracts\SnapshotService
{
    const DEFAULT_INTERVAL = 30;

    /**
     * @var StatsService
     */
    protected $statsService;

    /**
     * @inheritDoc
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function createSnapshot()
    {
        $entity = new Stats();
        $entity->setContext(static::getConfigManager()->getContext());
        $entity->setCreatedAt(new \DateTime());
        $entity->setSubscribed($this->getStatsService()->getSubscribed());
        $entity->setUnsubscribed($this->getStatsService()->getUnsubscribed());

        $this->getRepository()->save($entity);
    }

    /**
     * @inheritDoc
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Entity\Stats[]
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function getSnapshots()
    {
        $filter = new QueryFilter();
        $filter->where('context', Operators::EQUALS, static::getConfigManager()->getContext())
            ->setLimit($this->getInterval())
            ->orderBy('createdAt');

        /** @var Stats[] $stats */
        $stats = $this->getRepository()->select($filter);

        return $stats;
    }

    /**
     * @inheritDoc
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function remove()
    {
        $days = $this->getInterval();
        $interval = new \DateInterval("P{$days}D");
        $date = new \DateTime();
        $date->sub($interval);

        $filter = new QueryFilter();
        $filter->where('context', Operators::EQUALS, static::getConfigManager()->getContext())
            ->where('createdAt', Operators::LESS_THAN, $date)
            ->setLimit($this->getInterval())->orderBy('createdAt');

        $this->getRepository()->deleteWhere($filter);
    }

    /**
     * @inheritDoc
     * @return int|mixed|null
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function getInterval()
    {
        return static::getConfigManager()->getConfigValue('statsInterval', static::DEFAULT_INTERVAL);
    }

    /**
     * @inheritDoc
     * @param int $days
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function setInterval($days)
    {
        static::getConfigManager()->saveConfigValue('statsInterval', $days);
    }

    /**
     * @return StatsService
     */
    protected function getStatsService()
    {
        if (!$this->statsService) {
            $this->statsService = ServiceRegister::getService(StatsService::CLASS_NAME);
        }

        return $this->statsService;
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\ORM\Interfaces\ConditionallyDeletes|\Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Interfaces\RepositoryInterface
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getRepository()
    {
        $repository = RepositoryRegistry::getRepository(Stats::getClassName());
        if (!($repository instanceof ConditionallyDeletes)) {
            throw new RepositoryClassException('Stats repository must be instance of the ConditionallyDeletes interface');
        }

        return $repository;
    }

    /**
     * @return ConfigurationManager|object
     */
    protected static function getConfigManager()
    {
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME);
    }
}
