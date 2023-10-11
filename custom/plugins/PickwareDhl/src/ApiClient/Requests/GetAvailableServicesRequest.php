<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\ApiClient\Requests;

use DateTime;
use GuzzleHttp\Psr7\Request;

class GetAvailableServicesRequest extends Request
{
    private string $recipientZip;
    private DateTime $startDate;

    public function __construct(string $recipientZip, DateTime $startDate)
    {
        parent::__construct(
            'GET',
            sprintf(
                'checkout/%s/availableServices?%s',
                $recipientZip,
                http_build_query(['startDate' => $startDate->format('Y-m-d')]),
            ),
        );

        $this->recipientZip = $recipientZip;
        $this->startDate = $startDate;
    }

    public function getRecipientZip(): string
    {
        return $this->recipientZip;
    }

    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }
}
