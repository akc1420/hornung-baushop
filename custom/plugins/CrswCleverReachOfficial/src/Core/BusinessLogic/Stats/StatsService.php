<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Stats;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Special\Subscriber;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

/**
 * Class StatsService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Stats
 */
class StatsService implements Contracts\StatsService
{
    /**
     * @var Proxy
     */
    protected $proxy;
    /**
     * @var GroupService
     */
    protected $groupService;

    /**
     * @return int
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getSubscribed()
    {
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $tag = new Subscriber($configService->getIntegrationName());

        return (int)$this->getProxy()->countReceivers($tag, $this->getGroupService()->getId());
    }

    /**
     * @return int
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getUnsubscribed()
    {
        $stats = $this->getProxy()->getStats($this->getGroupService()->getId());

        return (int)$stats->getInactiveReceiverCount();
    }

    /**
     * @return Proxy
     */
    protected function getProxy()
    {
        if (!$this->proxy) {
            $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }

    /**
     * @return GroupService
     */
    protected function getGroupService()
    {
        if (!$this->groupService) {
            $this->groupService = ServiceRegister::getService(GroupService::CLASS_NAME);
        }

        return $this->groupService;
    }
}
