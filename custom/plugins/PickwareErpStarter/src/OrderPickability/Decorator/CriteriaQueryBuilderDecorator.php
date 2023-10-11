<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\OrderPickability\Decorator;

use Pickware\PickwareErpStarter\OrderPickability\OrderPickabilityCriteriaFilterResolver;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\CriteriaQueryBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\QueryBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\Filter;

class CriteriaQueryBuilderDecorator extends CriteriaQueryBuilder
{
    private CriteriaQueryBuilder $decoratedInstance;
    private OrderPickabilityCriteriaFilterResolver $orderPickabilityCriteriaFilterResolver;

    public function __construct(
        CriteriaQueryBuilder $decoratedInstance,
        OrderPickabilityCriteriaFilterResolver $orderPickabilityCriteriaFilterResolver
    ) {
        $this->decoratedInstance = $decoratedInstance;
        $this->orderPickabilityCriteriaFilterResolver = $orderPickabilityCriteriaFilterResolver;
    }

    public function build(
        QueryBuilder $query,
        EntityDefinition $definition,
        Criteria $criteria,
        Context $context,
        array $paths = []
    ): QueryBuilder {
        $this->orderPickabilityCriteriaFilterResolver->resolveOrderPickabilityFilter($criteria);

        return $this->decoratedInstance->build($query, $definition, $criteria, $context, $paths);
    }

    public function addFilter(
        EntityDefinition $definition,
        ?Filter $filter,
        QueryBuilder $query,
        Context $context
    ): void {
        $this->decoratedInstance->addFilter($definition, $filter, $query, $context);
    }

    public function addSortings(
        EntityDefinition $definition,
        Criteria $criteria,
        array $sortings,
        QueryBuilder $query,
        Context $context
    ): void {
        $this->decoratedInstance->addSortings($definition, $criteria, $sortings, $query, $context);
    }
}
