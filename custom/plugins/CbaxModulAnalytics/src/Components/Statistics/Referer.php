<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

use Cbax\ModulAnalytics\Components\Base;

class Referer
{
    private $config;
    private $base;
    private $refererRepository;

    public function __construct(
        $config,
        Base $base,
        EntityRepositoryInterface $refererRepository
    )
    {
        $this->config = $config;
        $this->base = $base;
        $this->refererRepository = $refererRepository;
    }

    public function getReferer($parameters, $context)
    {
        $criteria = $this->base->getBaseCriteria('date', $parameters, false);

        $criteria->addAggregation(
            new TermsAggregation(
                'count-by-referer',
                'referer',
                null,
                null,
            )
        );

        $referersResult = $this->refererRepository->search($criteria, $context);
        $aggregation = $referersResult->getAggregations()->get('count-by-referer');
        
        $data = [];

        foreach ($aggregation->getBuckets() as $bucket) {
            $key = $bucket->getKey();
            $refererCriteria = new Criteria();
            $refererCriteria->addFilter(new EqualsFilter('referer', $key));

            $refererResult = $this->refererRepository->search($refererCriteria, $context)->first();

            $data[] = [
                'date' => $refererResult->date->format('d.m.Y'),
                'referer' => $key,
                'deviceType' => $refererResult->deviceType,
                'counted' => $refererResult->counted
            ];
        }

        $sortedData = $this->base->sortArrayByColumn($data, 'date');
        
        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($data, $parameters['labels']);
        }

        return['gridData' => $sortedData];
    }
}


