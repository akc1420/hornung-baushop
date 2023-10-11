<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Events;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\Event;

/**
 * Class AfterFormCacheCreatedEvent
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Events
 */
class AfterFormCacheCreatedEvent extends Event
{
    const CLASS_NAME = __CLASS__;
    /**
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form
     */
    private $form;

    /**
     * AfterFormCacheCreatedEvent constructor.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form $form
     */
    public function __construct(Form $form)
    {
        $this->form = $form;
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form
     */
    public function getForm()
    {
        return $this->form;
    }
}