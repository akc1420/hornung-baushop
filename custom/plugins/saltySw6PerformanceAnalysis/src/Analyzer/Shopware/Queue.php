<?php

declare(strict_types=1);

namespace salty\Sw6PerformanceAnalysis\Analyzer\Shopware;

use DateTime;
use salty\Sw6PerformanceAnalysis\Analyzer\Analyzer;
use salty\Sw6PerformanceAnalysis\Struct\ResultCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;

class Queue extends Analyzer
{
    private const REQUIREMENTS = [
        'scheduledTasks' => [
            'expected'       => 0,
            'suggestedValue' => 0,
        ],
    ];

    /** @var EntityRepositoryInterface */
    private $scheduledTaskRepository;

    public function __construct(EntityRepositoryInterface $scheduledTaskRepository)
    {
        $this->scheduledTaskRepository = $scheduledTaskRepository;
    }

    public function analyze(ResultCollection $collection): ResultCollection
    {
        $this->checkScheduledTasksStatus($collection);

        return $collection;
    }

    private function checkScheduledTasksStatus(ResultCollection $collection): void
    {
        $date = new DateTime();
        $date->modify(sprintf('-%d minutes', 30));

        $criteria = new Criteria();
        $criteria->addFilter(
            new RangeFilter(
                'nextExecutionTime',
                ['lte' => $date->format(DATE_ATOM)]
            )
        );

        $oldTasks = $this->scheduledTaskRepository
            ->searchIds($criteria, Context::createDefaultContext())->getTotal();

        $this->getResult($collection, 'scheduledTasks', $oldTasks, self::REQUIREMENTS);
    }
}
