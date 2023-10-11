<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\Contracts;

use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Request\DynamicContentRequest;

/**
 * Interface DynamicContentHandler
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\Contracts
 */
interface DynamicContentHandler
{
    /**
     * Handles request to the dynamic content endpoint
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Request\DynamicContentRequest $request
     *
     * @return array
     */
    public function handle(DynamicContentRequest $request);
}
