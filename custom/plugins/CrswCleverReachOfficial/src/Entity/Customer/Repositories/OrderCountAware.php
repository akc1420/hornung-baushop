<?php

namespace Crsw\CleverReachOfficial\Entity\Customer\Repositories;

interface OrderCountAware
{
    /**
     * Counts orders for provided customer email
     *
     * @param string $customerEmail
     *
     * @return int
     */
    public function countOrders(string $customerEmail): int;
}
