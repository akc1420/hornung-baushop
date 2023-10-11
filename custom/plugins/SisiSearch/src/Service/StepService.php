<?php

namespace Sisi\Search\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class StepService
{
    /**
     * @param array $parameters
     * @param array $listingSettings
     * @param array $config
     * @return Criteria
     */
    public function getEntities($parameters, $listingSettings, $config)
    {
        $criteria = new Criteria();
        if (array_key_exists('limit', $parameters)) {
            if (is_numeric($parameters['limit'])) {
                $criteria->setLimit($parameters['limit']);
            }
        }

        if (array_key_exists('offset', $parameters)) {
            if (is_numeric($parameters['offset'])) {
                $criteria->setOffset($parameters['offset']);
            }
        }
        $this->setAbverkauf($criteria, $listingSettings, $config);
        $this->setTime($criteria, $parameters);
        return $criteria;
    }

    private function setTime(&$criteria, array $parameters)
    {
        if (array_key_exists("time", $parameters) && array_key_exists("update", $parameters)) {
            $time = time();
            $days = (int)$parameters["time"];
            $time = $time - ($days * 86400);
            $start = date('Y-m-d H:i:s', $time);
            $now = new \DateTime($start);
            if ($parameters['update'] === '1') {
                $criteria->addFilter(
                    new RangeFilter('createdAt', [RangeFilter::GTE => $now->format('Y-m-d H:i:s')])
                );
            }
            if ($parameters['update'] === '2') {
                $criteria->addFilter(
                    new MultiFilter(
                        MultiFilter::CONNECTION_OR,
                        [
                            new RangeFilter('createdAt', [RangeFilter::GTE => $now->format('Y-m-d H:i:s')]),
                            new RangeFilter('updatedAt', [RangeFilter::GTE => $now->format('Y-m-d H:i:s')]),
                        ]
                    )
                );
            }
        }
    }

    private function setAbverkauf(Criteria &$criteria, array $listingSettings, array $config): void
    {
        $strStock = true;
        if (array_key_exists('onlymain', $config)) {
            if ($config['onlymain'] === 'stock') {
                $strStock = false;
            }
        }
        if (array_key_exists('hideCloseoutProductsWhenOutOfStock', $listingSettings) && $strStock) {
            if ($listingSettings['hideCloseoutProductsWhenOutOfStock']) {
                $criteria->addFilter(
                    new MultiFilter(
                        MultiFilter::CONNECTION_OR,
                        [
                            new EqualsFilter('available', 1),
                            new MultiFilter(
                                MultiFilter::CONNECTION_AND,
                                [
                                    new EqualsFilter('available', 0),
                                    new EqualsFilter('isCloseout', 0)
                                ]
                            )
                        ]
                    )
                );
            }
        }
    }
}
