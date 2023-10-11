<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

use Cbax\ModulAnalytics\Components\Base;

class CustomerOnline {

    private $base;
    private $customerPoolRepository;
    private $customerRepository;

    public function __construct(
        Base $base,
        EntityRepositoryInterface $customerPoolRepository,
        EntityRepositoryInterface $customerRepository
    )
    {
        $this->base = $base;
        $this->customerPoolRepository = $customerPoolRepository;
        $this->customerRepository = $customerRepository;
    }

    public function getCustomerOnline($parameters, Context $context)
    {
        date_default_timezone_set('UTC');
        $date4Hours = date('Y-m-d h:i:s', strtotime("-4 hour"));
        $date2Hours = date('Y-m-d h:i:s', strtotime("-2 hour"));
        $date1Hours = date('Y-m-d h:i:s', strtotime("-1 hour"));
        $date30Minutes = date('Y-m-d h:i:s', strtotime("-30 minute"));
        $date10Minutes = date('Y-m-d h:i:s', strtotime("-10 minute"));

        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addAggregation(
            new FilterAggregation(
                '4-hours-visitors-filter',
                new CountAggregation('4-hours-visitors', 'id'),
                [new RangeFilter('createdAt', [
                    RangeFilter::GTE => $date4Hours
                ])]
            )
        );
        $criteria->addAggregation(
            new FilterAggregation(
                '2-hours-visitors-filter',
                new CountAggregation('2-hours-visitors', 'id'),
                [new RangeFilter('createdAt', [
                    RangeFilter::GTE => $date2Hours
                ])]
            )
        );
        $criteria->addAggregation(
            new FilterAggregation(
                '1-hours-visitors-filter',
                new CountAggregation('1-hours-visitors', 'id'),
                [new RangeFilter('createdAt', [
                    RangeFilter::GTE => $date1Hours
                ])]
            )
        );
        $criteria->addAggregation(
            new FilterAggregation(
                '30-minutes-visitors-filter',
                new CountAggregation('30-minutes-visitors', 'id'),
                [new RangeFilter('createdAt', [
                    RangeFilter::GTE => $date30Minutes
                ])]
            )
        );
        $criteria->addAggregation(
            new FilterAggregation(
                '10-minutes-visitors-filter',
                new CountAggregation('10-minutes-visitors', 'id'),
                [new RangeFilter('createdAt', [
                    RangeFilter::GTE => $date10Minutes
                ])]
            )
        );

        if (!empty($parameters['salesChannelIds']))
        {
            $criteria->addFilter(new EqualsAnyFilter('salesChannelId', $parameters['salesChannelIds']));
        }

        $result = $this->customerPoolRepository->search($criteria, $context);

        $criteria2 = new Criteria();
        $criteria2->setLimit(1);
        $criteria2->addAggregation(
            new FilterAggregation(
                '4-hours-logins-filter',
                new CountAggregation('4-hours-logins', 'id'),
                [new RangeFilter('lastLogin', [
                    RangeFilter::GTE => $date4Hours
                ])]
            )
        );
        $criteria2->addAggregation(
            new FilterAggregation(
                '2-hours-logins-filter',
                new CountAggregation('2-hours-logins', 'id'),
                [new RangeFilter('lastLogin', [
                    RangeFilter::GTE => $date2Hours
                ])]
            )
        );
        $criteria2->addAggregation(
            new FilterAggregation(
                '1-hours-logins-filter',
                new CountAggregation('1-hours-logins', 'id'),
                [new RangeFilter('lastLogin', [
                    RangeFilter::GTE => $date1Hours
                ])]
            )
        );
        $criteria2->addAggregation(
            new FilterAggregation(
                '30-minutes-logins-filter',
                new CountAggregation('30-minutes-logins', 'id'),
                [new RangeFilter('lastLogin', [
                    RangeFilter::GTE => $date30Minutes
                ])]
            )
        );
        $criteria2->addAggregation(
            new FilterAggregation(
                '10-minutes-logins-filter',
                new CountAggregation('10-minutes-logins', 'id'),
                [new RangeFilter('lastLogin', [
                    RangeFilter::GTE => $date10Minutes
                ])]
            )
        );

        if (!empty($parameters['salesChannelIds']))
        {
            $criteria2->addFilter(new EqualsAnyFilter('salesChannelId', $parameters['salesChannelIds']));
        }

        $result2 = $this->customerRepository->search($criteria2, $context);

        $data = [];
        $data[] = [
            'time' => 'cbax-analytics.view.customerOnline.4h',
            'visitors' => $result->getAggregations()->get('4-hours-visitors')->getCount(),
            'logins' => $result2->getAggregations()->get('4-hours-logins')->getCount()
        ];
        $data[] = [
            'time' => 'cbax-analytics.view.customerOnline.2h',
            'visitors' => $result->getAggregations()->get('2-hours-visitors')->getCount(),
            'logins' => $result2->getAggregations()->get('2-hours-logins')->getCount()
        ];
        $data[] = [
            'time' => 'cbax-analytics.view.customerOnline.1h',
            'visitors' => $result->getAggregations()->get('1-hours-visitors')->getCount(),
            'logins' => $result2->getAggregations()->get('1-hours-logins')->getCount()
        ];
        $data[] = [
            'time' => 'cbax-analytics.view.customerOnline.30min',
            'visitors' => $result->getAggregations()->get('30-minutes-visitors')->getCount(),
            'logins' => $result2->getAggregations()->get('30-minutes-logins')->getCount()
        ];
        $data[] = [
            'time' => 'cbax-analytics.view.customerOnline.10min',
            'visitors' => $result->getAggregations()->get('10-minutes-visitors')->getCount(),
            'logins' => $result2->getAggregations()->get('10-minutes-logins')->getCount()
        ];

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($data, $parameters['labels']);
        }

        return $data;
    }
}
