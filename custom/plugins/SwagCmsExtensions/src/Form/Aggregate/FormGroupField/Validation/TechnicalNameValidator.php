<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroupField\Validation;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\UpdateCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;
use Swag\CmsExtensions\Form\Aggregate\FormGroup\FormGroupDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldDefinition;
use Swag\CmsExtensions\Form\FormDefinition;
use Swag\CmsExtensions\Util\Administration\FormValidationController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TechnicalNameValidator implements EventSubscriberInterface
{
    private Connection $connection;

    private ValidatorInterface $validator;

    public function __construct(Connection $connection, ValidatorInterface $validator)
    {
        $this->connection = $connection;
        $this->validator = $validator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreWriteValidationEvent::class => 'preValidate',
        ];
    }

    public function preValidate(PreWriteValidationEvent $event): void
    {
        if ($event->getContext()->hasExtension(FormValidationController::IS_FORM_VALIDATION)) {
            // Skips validation, because administration has own unique validation
            // This is needed, because simultaneous delete and create with same technicalName can not be validated
            return;
        }

        $violationList = new ConstraintViolationList();
        foreach ($event->getCommands() as $command) {
            if (!($command instanceof InsertCommand || $command instanceof UpdateCommand)) {
                continue;
            }

            if ($command->getDefinition()->getClass() !== FormGroupFieldDefinition::class) {
                continue;
            }

            $violationList->addAll($this->validateTechnicalName($command, $event));
        }

        if ($violationList->count() > 0) {
            $event->getExceptions()->add(new WriteConstraintViolationException($violationList));

            return;
        }
    }

    private function validateTechnicalName(WriteCommand $command, PreWriteValidationEvent $event): ConstraintViolationListInterface
    {
        $payload = $command->getPayload();

        if (!isset($payload['technical_name'])) {
            return new ConstraintViolationList();
        }

        $violationList = $this->validator->startContext()
            ->atPath(\sprintf('%s/technicalName', $command->getPath()))
            ->validate(
                $payload['technical_name'],
                new Regex([
                    'pattern' => "/^[^\s]+$/i",
                    'message' => 'The technical name may not include whitespace characters.',
                ])
            )
            ->getViolations();

        $formId = $this->getFormId($command, $event);

        if ($formId === null) {
            return $violationList;
        }

        if ($this->isTechnicalNameUniqueInForm($command->getPrimaryKey()['id'], $payload['technical_name'], $formId, $event)) {
            return $violationList;
        }

        $messageTemplate = 'The technical name (%value%) is not unique in this form.';
        $parameters = ['%value%' => $payload['technical_name'] ?? 'NULL'];

        $violationList->add(new ConstraintViolation(
            \str_replace(\array_keys($parameters), $parameters, $messageTemplate),
            $messageTemplate,
            $parameters,
            null,
            \sprintf('%s/technicalName', $command->getPath()),
            null
        ));

        return $violationList;
    }

    private function getFormId(WriteCommand $command, PreWriteValidationEvent $event, bool $loadFromConnection = true): ?string
    {
        foreach ($event->getCommands() as $groupCommand) {
            if (!($groupCommand instanceof InsertCommand || $groupCommand instanceof UpdateCommand)
                || $groupCommand->getDefinition()->getClass() !== FormGroupDefinition::class
            ) {
                continue;
            }

            $pathDiff = $groupCommand->getPath();
            $pos = \mb_strpos($command->getPath(), $groupCommand->getPath());
            if ($pos !== false) {
                $pathDiff = \substr_replace($command->getPath(), '', $pos, \mb_strlen($groupCommand->getPath()));
            }

            $matches = [];
            \preg_match('/^\/fields\/\d+/', $pathDiff, $matches);
            if (empty($matches)) {
                // are we writing a field with a group "underneath"
                $pathDiff = \str_replace($command->getPath(), '', $groupCommand->getPath());
                \preg_match('/^\/group/', $pathDiff, $matches);
            }

            if (!empty($matches)) {
                $payload = $groupCommand->getPayload();

                if (isset($payload[\sprintf('%s_id', FormDefinition::ENTITY_NAME)])) {
                    return $payload[\sprintf('%s_id', FormDefinition::ENTITY_NAME)];
                }
            }
        }

        if (!$loadFromConnection) {
            return null;
        }

        $payload = $command->getPayload();
        $groupId = $payload[\sprintf('%s_id', FormGroupDefinition::ENTITY_NAME)] ?? null;

        if ($groupId === null) {
            return null;
        }

        $query = $this->connection->createQueryBuilder()
            ->select(\sprintf('%s_id', FormDefinition::ENTITY_NAME))
            ->from(FormGroupDefinition::ENTITY_NAME)
            ->where('id = :id')
            ->setParameter('id', $groupId)
            ->setMaxResults(1)
            ->execute();

        if (!($query instanceof ResultStatement)) {
            return null;
        }

        $formId = $query->fetchColumn();

        return $formId !== false ? $formId : null;
    }

    private function isTechnicalNameUniqueInForm(string $fieldId, string $technicalName, string $formId, PreWriteValidationEvent $event): bool
    {
        $ignoredIds = [$fieldId];

        foreach ($event->getCommands() as $fieldCommand) {
            if ($fieldCommand->getDefinition()->getClass() !== FormGroupFieldDefinition::class) {
                continue;
            }

            if ($fieldId === $fieldCommand->getPrimaryKey()['id']) {
                continue;
            }

            if ($fieldCommand instanceof DeleteCommand) {
                $ignoredIds[] = $fieldCommand->getPrimaryKey()['id'];

                continue;
            }

            $otherFormId = $this->getFormId($fieldCommand, $event, false);

            if ($otherFormId !== $formId) {
                continue;
            }

            $payload = $fieldCommand->getPayload();
            if (isset($payload['technical_name']) && $payload['technical_name'] === $technicalName) {
                return false;
            }
        }

        $query = $this->connection->createQueryBuilder()
            ->select('field.technical_name')
            ->from(FormGroupFieldDefinition::ENTITY_NAME, 'field')
            ->leftJoin('field', FormGroupDefinition::ENTITY_NAME, 'formgroup', \sprintf('formgroup.id = field.%s_id', FormGroupDefinition::ENTITY_NAME))
            ->where('field.technical_name = :technical_name')
            ->andWhere('field.id NOT IN (:ids)')
            ->andWhere(\sprintf('formgroup.%s_id = :form_id', FormDefinition::ENTITY_NAME))
            ->setParameter('technical_name', $technicalName)
            ->setParameter('form_id', $formId)
            ->setParameter('ids', $ignoredIds, Connection::PARAM_STR_ARRAY)
            ->setMaxResults(1)
            ->execute();

        if (!($query instanceof ResultStatement)) {
            return true;
        }

        return !(bool) $query->fetchColumn();
    }
}
