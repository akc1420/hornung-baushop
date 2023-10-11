<?php

namespace Crsw\CleverReachOfficial\Components\FormListeners;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Events\BeforeFormCacheCreatedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Events\BeforeFormCacheUpdatedEvent;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Form\ContentTransformer;

/**
 * Class TransformFormContent
 *
 * @package Crsw\CleverReachOfficial\Components\FormListeners
 */
class TransformFormContent
{
    /**
     * @param BeforeFormCacheCreatedEvent | BeforeFormCacheUpdatedEvent $event
     */
    public static function handle($event): void
    {
        $form = $event->getForm();
        $form->setContent(ContentTransformer::transform($form->getContent()));
    }
}