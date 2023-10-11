<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\GoodsReceipt;

use Pickware\InstallationLibrary\StateMachine\StateMachine;
use Pickware\InstallationLibrary\StateMachine\StateMachineState;

class GoodsReceiptStateMachine extends StateMachine
{
    public const TECHNICAL_NAME = 'pickware_erp_goods_receipt.state';

    public const STATE_COMPLETED = 'completed';

    public function __construct()
    {
        $completed = new StateMachineState(self::STATE_COMPLETED, [
            'de-DE' => 'Abgeschlossen',
            'en-GB' => 'Completed',
        ]);

        parent::__construct(
            self::TECHNICAL_NAME,
            [
                'de-DE' => 'Wareneingangsstatus',
                'en-GB' => 'Stock receipt state',
            ],
            [$completed],
            $completed,
        );
    }
}
