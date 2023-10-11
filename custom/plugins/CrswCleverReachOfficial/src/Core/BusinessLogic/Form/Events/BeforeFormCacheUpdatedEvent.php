<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Events;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\Event;

class BeforeFormCacheUpdatedEvent extends Event
{
    const CLASS_NAME = __CLASS__;
    /**
     * @var Form
     */
    private $form;

    /**
     * BeforeFormCacheUpdatedEvent constructor.
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