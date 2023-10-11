<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroupField\Validation;

use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\UpdateCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldTypeRegistry;
use Swag\CmsExtensions\Util\Administration\FormValidationController;
use Swag\CmsExtensions\Util\Exception\FormValidationPassedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class TypeValidator implements EventSubscriberInterface
{
    private FormGroupFieldTypeRegistry $typeRegistry;

    public function __construct(FormGroupFieldTypeRegistry $typeRegistry)
    {
        $this->typeRegistry = $typeRegistry;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreWriteValidationEvent::class => 'preValidate',
        ];
    }

    public function preValidate(PreWriteValidationEvent $event): void
    {
        $violationList = new ConstraintViolationList();
        foreach ($event->getCommands() as $command) {
            if (!($command instanceof InsertCommand || $command instanceof UpdateCommand)) {
                continue;
            }

            if ($command->getDefinition()->getClass() !== FormGroupFieldDefinition::class) {
                continue;
            }

            $violationList->addAll($this->validateType($command));
        }

        if ($violationList->count() > 0) {
            $event->getExceptions()->add(new WriteConstraintViolationException($violationList));

            return;
        }

        if ($event->getContext()->hasExtension(FormValidationController::IS_FORM_VALIDATION)) {
            $event->getExceptions()->add(new FormValidationPassedException());
        }
    }

    private function validateType(WriteCommand $command): ConstraintViolationList
    {
        $violationList = new ConstraintViolationList();

        $payload = $command->getPayload();

        if (!isset($payload['type'])) {
            return $violationList;
        }

        $type = $this->typeRegistry->getType($payload['type'] ?? '');

        if ($type !== null) {
            return $violationList;
        }

        $messageTemplate = 'This "type" value (%value%) is invalid.';
        $parameters = ['%value%' => $payload['type'] ?? 'NULL'];

        $violationList->add(new ConstraintViolation(
            \str_replace(\array_keys($parameters), $parameters, $messageTemplate),
            $messageTemplate,
            $parameters,
            null,
            \sprintf('%s/type', $command->getPath()),
            null
        ));

        return $violationList;
    }
}
