<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Validation;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\UpdateCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;
use Swag\CmsExtensions\Form\FormDefinition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class TechnicalNameValidator implements EventSubscriberInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
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

            if ($command->getDefinition()->getClass() !== FormDefinition::class) {
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
        $violationList = new ConstraintViolationList();
        $payload = $command->getPayload();

        if (!isset($payload['technical_name'])) {
            return $violationList;
        }

        if ($this->isTechnicalNameUnique($command->getPrimaryKey()['id'], $payload['technical_name'], $event)) {
            return $violationList;
        }

        $messageTemplate = 'The technical name (%value%) of this form is not unique or a form template with this name already exists.';
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

    private function isTechnicalNameUnique(string $formId, string $technicalName, PreWriteValidationEvent $event): bool
    {
        $ignoredIds = [$formId];

        foreach ($event->getCommands() as $formCommand) {
            if ($formCommand->getDefinition()->getClass() !== FormDefinition::class) {
                continue;
            }

            $otherId = $formCommand->getPrimaryKey()['id'];
            if ($formId === $otherId) {
                continue;
            }

            if ($formCommand instanceof DeleteCommand) {
                $ignoredIds[] = $formCommand->getPrimaryKey()['id'];

                continue;
            }

            $payload = $formCommand->getPayload();
            if (isset($payload['technical_name']) && $payload['technical_name'] === $technicalName) {
                return false;
            }
        }

        $query = $this->connection->createQueryBuilder()
            ->select('technical_name')
            ->from(FormDefinition::ENTITY_NAME)
            ->where('technical_name = :technical_name')
            ->andWhere('id NOT IN (:ids)')
            ->setParameter('technical_name', $technicalName)
            ->setParameter('ids', $ignoredIds, Connection::PARAM_STR_ARRAY)
            ->setMaxResults(1)
            ->execute();

        if (!($query instanceof ResultStatement)) {
            return true;
        }

        return !(bool) $query->fetchColumn();
    }
}
