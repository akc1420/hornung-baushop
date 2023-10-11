<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\ORM\Interfaces;

use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;

/**
 * Interface ConditionallyDeletes
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\ORM\Interfaces
 */
interface ConditionallyDeletes
{
    public function deleteWhere(QueryFilter $queryFilter = null);
}