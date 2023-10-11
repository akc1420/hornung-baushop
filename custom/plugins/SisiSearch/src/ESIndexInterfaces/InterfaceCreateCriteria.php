<?php

namespace Sisi\Search\ESIndexInterfaces;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

interface InterfaceCreateCriteria
{
    public function getCriteria(Criteria &$criteria): void;
}
