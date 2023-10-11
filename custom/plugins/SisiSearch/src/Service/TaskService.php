<?php

namespace Sisi\Search\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Sisi\Search\Core\Content\Task\Bundle\DBSchedularEntity;

class TaskService
{
    /**
     * @param DBSchedularEntity  $result
     * @param EntityRepository  $repository
     * @param Context  $context
     *
     * @return bool
     */
    public function ifLogik($result, $repository, $context): bool
    {
        $nextTime = $result->getNextExecutionTime();
        $now = new \DateTime("now");
        if ($nextTime !== null) {
            if ($nextTime->getTimestamp() <= $now->getTimestamp()) {
                $nextTimesec = $now->getTimestamp() + $result->getTime();
                $next = new \DateTime();
                $next->setTimestamp($nextTimesec);
                $repository->update([
                                        [
                                            'id' => $result->getId(),
                                            'lastExecutionTime' => $now,
                                            'nextExecutionTime' => $next
                                        ]
                                    ], $context);
                return true;
            }
        }
        return false;
    }

    /**
     * @param EntityRepository $repository
     * @param  Context $context
     * @return array
     */
    public function addAllSisiTask($repository, $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('aktive', 'yes'));
        return $repository->search($criteria, $context)->getEntities()->getElements();
    }
}
