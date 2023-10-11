<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\ReturnOrder;

use Pickware\InstallationLibrary\StateMachine\StateMachine;
use Pickware\InstallationLibrary\StateMachine\StateMachineState;

class ReturnOrderStateMachine extends StateMachine
{
    public const TECHNICAL_NAME = 'pickware_erp_return_order.state';

    public const STATE_REQUESTED = 'requested';
    public const STATE_OPEN = 'open';
    public const STATE_COMPLETED = 'completed';

    public const TRANSITION_APPROVE = 'approve';
    public const TRANSITION_COMPLETE = 'complete';

    public function __construct()
    {
        $requested = new StateMachineState(self::STATE_REQUESTED, [
            'de-DE' => 'Angekündigt',
            'en-GB' => 'Requested',
        ]);
        $open = new StateMachineState(self::STATE_OPEN, [
            'de-DE' => 'Offen',
            'en-GB' => 'Open',
        ]);
        $completed = new StateMachineState(self::STATE_COMPLETED, [
            'de-DE' => 'Abgeschlossen',
            'en-GB' => 'Completed',
        ]);

        $requested->addTransitionToState($open, self::TRANSITION_APPROVE);
        $open->addTransitionToState($completed, self::TRANSITION_COMPLETE);

        parent::__construct(
            self::TECHNICAL_NAME,
            [
                'de-DE' => 'Rückgabe',
                'en-GB' => 'Return',
            ],
            [
                $requested,
                $open,
                $completed,
            ],
            $requested,
        );
    }
}
