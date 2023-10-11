<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroupField\Validation;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\UpdateCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldTypeRegistry;
use Swag\CmsExtensions\Form\Aggregate\FormGroupFieldTranslation\FormGroupFieldTranslationDefinition;
use Swag\CmsExtensions\Util\Administration\FormValidationController;
use Swag\CmsExtensions\Util\Exception\FormValidationPassedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConfigValidator implements EventSubscriberInterface
{
    public const CONFIG_ROOT_CONSTRAINTS = '_root';

    private FormGroupFieldTypeRegistry $typeRegistry;

    private ValidatorInterface $validator;

    private Connection $connection;

    public function __construct(
        ValidatorInterface $validator,
        FormGroupFieldTypeRegistry $typeRegistry,
        Connection $connection
    ) {
        $this->validator = $validator;
        $this->typeRegistry = $typeRegistry;
        $this->connection = $connection;
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

            if ($command->getDefinition()->getClass() !== FormGroupFieldTranslationDefinition::class) {
                continue;
            }

            $violationList->addAll($this->validateConfig($command, $event));
        }

        if ($violationList->count() > 0) {
            $event->getExceptions()->add(new WriteConstraintViolationException($violationList));

            return;
        }

        if ($event->getContext()->hasExtension(FormValidationController::IS_FORM_VALIDATION)) {
            $event->getExceptions()->add(new FormValidationPassedException());
        }
    }

    private function validateConfig(WriteCommand $command, PreWriteValidationEvent $event): ConstraintViolationList
    {
        $violationList = new ConstraintViolationList();

        $payload = $command->getPayload();
        $payload['config'] = $this->decodeConfig($payload);

        $typeName = $this->getFieldTypeOfTranslation($command, $event);
        $type = $this->typeRegistry->getType($typeName ?? '');

        if ($type === null) {
            $violationList->add(
                $this->buildViolation(
                    'Field type could not be resolved and configuration could not be validated.',
                    [],
                    \sprintf('%s/config', $command->getPath())
                )
            );

            return $violationList;
        }

        $constraints = $type->getConfigConstraints();

        if (isset($constraints[self::CONFIG_ROOT_CONSTRAINTS])) {
            $violationList->addAll(
                $this->validate(
                    $command->getPath(),
                    ['config' => $constraints[self::CONFIG_ROOT_CONSTRAINTS]],
                    $payload,
                    true
                )
            );
            unset($constraints[self::CONFIG_ROOT_CONSTRAINTS]);
        }

        $configPath = \sprintf('%s/config', $command->getPath());

        if (!empty($payload['config'])) {
            $violationList->addAll($this->validate($configPath, $constraints, $payload['config']));
            $violationList->addAll($type->validateConfig($payload['config'], $configPath));
        }

        return $violationList;
    }

    /**
     * @param array<string, string> $parameters
     */
    private function buildViolation(
        string $messageTemplate,
        array $parameters,
        string $propertyPath
    ): ConstraintViolationInterface {
        return new ConstraintViolation(
            \str_replace(\array_keys($parameters), $parameters, $messageTemplate),
            $messageTemplate,
            $parameters,
            null,
            $propertyPath,
            null
        );
    }

    /**
     * @param array<string, Constraint|array<Constraint>|null> $fieldValidations
     * @param array<string, mixed> $payload
     */
    private function validate(string $basePath, array $fieldValidations, array $payload, bool $allowUnknownFields = false): ConstraintViolationList
    {
        $violations = new ConstraintViolationList();
        foreach ($fieldValidations as $fieldName => $validations) {
            $currentPath = \sprintf('%s/%s', $basePath, $fieldName);
            $violations->addAll(
                $this->validator->startContext()
                    ->atPath($currentPath)
                    ->validate($payload[$fieldName] ?? null, $validations)
                    ->getViolations()
            );
        }

        if ($allowUnknownFields) {
            return $violations;
        }

        foreach ($payload as $fieldName => $_value) {
            if (!\array_key_exists($fieldName, $fieldValidations)) {
                $currentPath = \sprintf('%s/%s', $basePath, $fieldName);

                $violations->add(
                    $this->buildViolation(
                        'The property "{{ fieldName }}" is not allowed.',
                        ['{{ fieldName }}' => $fieldName],
                        $currentPath
                    )
                );
            }
        }

        return $violations;
    }

    /**
     * @param array<string, string|mixed> $payload
     *
     * @return array<mixed>
     */
    private function decodeConfig(array $payload): ?array
    {
        if (!\array_key_exists('config', $payload) || $payload['config'] === null) {
            return null;
        }

        $config = \json_decode($payload['config'], true, 512, \JSON_THROW_ON_ERROR);

        foreach ($config as $key => $val) {
            if ($val === null) {
                unset($config[$key]);
            }
        }

        return $config;
    }

    private function getFieldTypeOfTranslation(WriteCommand $command, PreWriteValidationEvent $event): ?string
    {
        foreach ($event->getCommands() as $fieldCommand) {
            if (!($fieldCommand instanceof InsertCommand || $fieldCommand instanceof UpdateCommand)
                || $fieldCommand->getDefinition()->getClass() !== FormGroupFieldDefinition::class
            ) {
                continue;
            }

            $pathDiff = \str_replace($fieldCommand->getPath(), '', $command->getPath());

            $matches = [];
            \preg_match('/^\/translations\/[A-Fa-f0-9]{32}/', $pathDiff, $matches);
            if (!empty($matches)) {
                $payload = $fieldCommand->getPayload();

                if (isset($payload['type'])) {
                    return $payload['type'];
                }
            }
        }

        $fieldId = $command->getPrimaryKey()[\sprintf('%s_id', FormGroupFieldDefinition::ENTITY_NAME)] ?? null;

        if ($fieldId === null) {
            return null;
        }

        $query = $this->connection->createQueryBuilder()
            ->select('type')
            ->from(FormGroupFieldDefinition::ENTITY_NAME)
            ->where('id = :id')
            ->setParameter('id', $fieldId)
            ->setMaxResults(1)
            ->execute();

        if (!($query instanceof ResultStatement)) {
            return null;
        }

        $fieldType = $query->fetchColumn();

        return $fieldType !== false ? $fieldType : null;
    }
}
