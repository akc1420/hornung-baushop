<?php

namespace Recommendy\Services\Interfaces;

use Recommendy\Components\Struct\ActionStruct;
use Shopware\Core\Framework\Context;

interface TrackingServiceInterface
{
    /**
     * @param ActionStruct $struct
     * @param Context $context
     */
    public function handleTracking(ActionStruct $struct, Context $context);
}
