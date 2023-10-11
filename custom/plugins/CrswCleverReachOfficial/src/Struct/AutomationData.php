<?php

namespace Crsw\CleverReachOfficial\Struct;

use Shopware\Core\Framework\Struct\Struct;

/**
 * Class AutomationData
 *
 * @package Crsw\CleverReachOfficial\Struct
 */
class AutomationData extends Struct
{
    /**
     * @var array
     */
    public $automationData;

    /**
     * AutomationData constructor.
     *
     * @param array $automationData
     */
    public function __construct(array $automationData)
    {
        $this->automationData = $automationData;
    }
}