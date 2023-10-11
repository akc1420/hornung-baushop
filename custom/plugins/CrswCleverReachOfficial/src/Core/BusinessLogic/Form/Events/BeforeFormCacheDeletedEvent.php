<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Events;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\Event;

class BeforeFormCacheDeletedEvent extends Event
{
    const CLASS_NAME = __CLASS__;
    /**
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form
     */
    private $form;

    /**
     * BeforeFormCacheDeletedEvent constructor.
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