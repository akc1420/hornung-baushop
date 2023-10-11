<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Contracts;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Segment;

/**
 * Interface SegmentService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\Contracts
 */
interface SegmentService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieves list of available segments.
     *
     * @return Segment[] The list of available segments.
     */
    public function getSegments();

    /**
     * Returns segment filtered by condition
     *
     * @param string $filter
     *
     * @return Segment|null
     */
    public function getSegment($filter);
}
