<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\DalBundle;

use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use RuntimeException;
use Shopware\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Rule\RuleDefinition;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateDefinition;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\Currency\CurrencyDefinition;
use Shopware\Core\System\Locale\LocaleDefinition;
use Shopware\Core\System\Salutation\SalutationDefinition;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateDefinition;
use Shopware\Core\System\StateMachine\StateMachineDefinition;
use function Symfony\Component\Translation\t;

/**
 * Service to resolve system-specific IDs for entities with unique identifiers that are the same on every system.
 * Example: Order State, Country, ...
 *
 * Do not refactor this service to return entities instead of IDs. We don't want entities to be returned by services and
 * also for returning entities, a Context would be required. This service's methods should not have a context parameter.
 */
class EntityIdResolver
{
    public const DEFAULT_RULE_NAME = 'Always valid (Default)';

    private ?Connection $connection = null;
    private ?EntityManager $entityManager = null;
    private Context $context;

    /**
     * @param EntityManager|Connection $entityManagerOrConnection
     */
    public function __construct($entityManagerOrConnection)
    {
        /**
         * @deprecated tag:next-major First constructor argument will be changed to only accept a Connection as
         * argument.
         */
        if ($entityManagerOrConnection instanceof EntityManager) {
            $this->entityManager = $entityManagerOrConnection;
            $this->context = new Context(new SystemSource());
        } elseif ($entityManagerOrConnection instanceof Connection) {
            $this->connection = $entityManagerOrConnection;
        } else {
            throw new InvalidArgumentException(
                'First constructor argument must be of type %s or %s.',
                Connection::class,
                EntityManager::class,
            );
        }
    }

    /**
     * The country ISO code is not unique among the countries. Select the oldest country that matches instead.
     */
    public function resolveIdForCountry(string $isoCountryCode): string
    {
        if (!$this->connection) {
            return $this->resolveIdForCountryWithEntityManager($isoCountryCode);
        }

        /** @var string|false $id */
        $id = $this->connection->fetchOne(
            'SELECT LOWER(HEX(`id`)) FROM `country`
            WHERE `iso` = :isoCountryCode
            ORDER BY `created_at` ASC
            LIMIT 1',
            ['isoCountryCode' => $isoCountryCode],
        );
        if ($id === false) {
            throw new RuntimeException(sprintf('No country found for country ISO code "%s".', $isoCountryCode));
        }

        return $id;
    }

    private function resolveIdForCountryWithEntityManager(string $isoCountryCode): string
    {
        $criteria = EntityManager::createCriteriaFromArray(['iso' => $isoCountryCode]);
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::ASCENDING));

        return $this->entityManager->getFirstBy(
            CountryDefinition::class,
            $criteria,
            $this->context,
        )->getId();
    }

    public function resolveIdForOrderState(string $technicalName): string
    {
        return $this->resolveIdForStateMachineState(OrderStates::STATE_MACHINE, $technicalName);
    }

    public function resolveIdForOrderDeliveryState(string $technicalName): string
    {
        return $this->resolveIdForStateMachineState(OrderDeliveryStates::STATE_MACHINE, $technicalName);
    }

    public function resolveIdForOrderTransactionState(string $technicalName): string
    {
        return $this->resolveIdForStateMachineState(OrderTransactionStates::STATE_MACHINE, $technicalName);
    }

    public function resolveIdForStateMachineState(
        string $stateMachineTechnicalName,
        string $stateTechnicalName
    ): string {
        if (!$this->connection) {
            return $this->resolveIdForStateMachineStateWithEntityManager(
                $stateMachineTechnicalName,
                $stateTechnicalName,
            );
        }

        /** @var string|false $id */
        $id = $this->connection->fetchOne(
            'SELECT LOWER(HEX(`state_machine_state`.`id`))
            FROM `state_machine_state`
            INNER JOIN `state_machine` ON `state_machine`.`id` = `state_machine_state`.`state_machine_id`
            WHERE `state_machine_state`.`technical_name` = :stateTechnicalName
            AND `state_machine`.`technical_name` = :stateMachineTechnicalName
            LIMIT 1',
            [
                'stateTechnicalName' => $stateTechnicalName,
                'stateMachineTechnicalName' => $stateMachineTechnicalName,
            ],
        );
        if ($id === false) {
            throw new RuntimeException(sprintf(
                'No state machine state found for technical name "%s" in state machine "%s".',
                $stateTechnicalName,
                $stateMachineTechnicalName,
            ));
        }

        return $id;
    }

    private function resolveIdForStateMachineStateWithEntityManager(
        string $stateMachineTechnicalName,
        string $stateTechnicalName
    ): string {
        return $this->entityManager->getOneBy(
            StateMachineStateDefinition::class,
            [
                'stateMachine.technicalName' => $stateMachineTechnicalName,
                'technicalName' => $stateTechnicalName,
            ],
            $this->context,
        )->getId();
    }

    public function resolveIdForDocumentType(string $technicalName): string
    {
        if (!$this->connection) {
            return $this->resolveIdForDocumentTypeWithEntityManager($technicalName);
        }

        /** @var string|false $id */
        $id = $this->connection->fetchOne(
            'SELECT LOWER(HEX(`id`)) FROM `document_type` WHERE `technical_name` = :technicalName LIMIT 1',
            ['technicalName' => $technicalName],
        );
        if ($id === false) {
            throw new RuntimeException(sprintf('No document type found for technical name "%s".', $technicalName));
        }

        return $id;
    }

    private function resolveIdForDocumentTypeWithEntityManager(string $technicalName): string
    {
        return $this->entityManager->getOneBy(
            DocumentTypeDefinition::class,
            ['technicalName' => $technicalName],
            $this->context,
        )->getId();
    }

    /**
     * The country state short code is not unique among the country states. Select the oldest country state that matches
     * instead.
     */
    public function resolveIdForCountryState(string $isoCountryStateCode): string
    {
        if (!$this->connection) {
            return $this->resolveIdForCountryStateWithEntityManager($isoCountryStateCode);
        }

        /** @var string|false $id */
        $id = $this->connection->fetchOne(
            'SELECT LOWER(HEX(`id`)) FROM `country_state`
            WHERE `short_code` = :isoCountryStateCode
            ORDER BY `created_at` ASC
            LIMIT 1',
            ['isoCountryStateCode' => $isoCountryStateCode],
        );
        if ($id === false) {
            throw new RuntimeException(sprintf('No country state found for country state code "%s".', $isoCountryStateCode));
        }

        return $id;
    }

    private function resolveIdForCountryStateWithEntityManager(string $isoCountryStateCode): string
    {
        $criteria = EntityManager::createCriteriaFromArray(['shortCode' => $isoCountryStateCode]);
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::ASCENDING));

        return $this->entityManager->getFirstBy(
            CountryStateDefinition::class,
            $criteria,
            $this->context,
        )->getId();
    }

    public function resolveIdForSalutation(string $salutationKey): string
    {
        if (!$this->connection) {
            return $this->resolveIdForSalutationWithEntityManager($salutationKey);
        }

        /** @var string|false $id */
        $id = $this->connection->fetchOne(
            'SELECT LOWER(HEX(`id`)) FROM `salutation` WHERE `salutation_key` = :salutationKey LIMIT 1',
            ['salutationKey' => $salutationKey],
        );
        if ($id === false) {
            throw new RuntimeException(sprintf('No salutation found for salutation key "%s".', $salutationKey));
        }

        return $id;
    }

    private function resolveIdForSalutationWithEntityManager(string $salutationKey): string
    {
        return $this->entityManager->getOneBy(
            SalutationDefinition::class,
            ['salutationKey' => $salutationKey],
            $this->context,
        )->getId();
    }

    public function resolveIdForCurrency(string $isoCurrencyCode): string
    {
        if (!$this->connection) {
            return $this->resolveIdForCurrencyWithEntityManager($isoCurrencyCode);
        }

        /** @var string|false $id */
        $id = $this->connection->fetchOne(
            'SELECT LOWER(HEX(`id`)) FROM `currency` WHERE `iso_code` = :isoCurrencyCode LIMIT 1',
            ['isoCurrencyCode' => $isoCurrencyCode],
        );
        if ($id === false) {
            throw new RuntimeException(sprintf('No currency found for iso code "%s".', $isoCurrencyCode));
        }

        return $id;
    }

    private function resolveIdForCurrencyWithEntityManager(string $isoCurrencyCode): string
    {
        return $this->entityManager->getOneBy(
            CurrencyDefinition::class,
            ['isoCode' => $isoCurrencyCode],
            $this->context,
        )->getId();
    }

    public function resolveIdForLocale(string $code): string
    {
        if (!$this->connection) {
            return $this->resolveIdForLocaleWithEntityManager($code);
        }

        /** @var string|false $id */
        $id = $this->connection->fetchOne(
            'SELECT LOWER(HEX(`id`)) FROM `locale` WHERE `code` = :code LIMIT 1',
            ['code' => $code],
        );
        if ($id === false) {
            throw new RuntimeException(sprintf('No locale found for code "%s".', $code));
        }

        return $id;
    }

    private function resolveIdForLocaleWithEntityManager(string $code): string
    {
        return $this->entityManager->getOneBy(
            LocaleDefinition::class,
            ['code' => $code],
            $this->context,
        )->getId();
    }

    /**
     * There is no single root category in Shopware. We select "a" root category that is the oldest instead.
     */
    public function getRootCategoryId(): string
    {
        if (!$this->connection) {
            return $this->getRootCategoryIdWithEntityManager();
        }

        /** @var string|false $id */
        $id = $this->connection->fetchOne(
            'SELECT LOWER(HEX(`id`)) FROM `category`
            WHERE `parent_id` IS NULL
            ORDER BY `created_at` ASC
            LIMIT 1',
        );
        if ($id === false) {
            throw new RuntimeException('No root category found.');
        }

        return $id;
    }

    private function getRootCategoryIdWithEntityManager(): string
    {
        $criteria = EntityManager::createCriteriaFromArray(['parentId' => null]);
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::ASCENDING));

        return $this->entityManager->getFirstBy(
            CategoryDefinition::class,
            $criteria,
            $this->context,
        )->getId();
    }

    /**
     * Returns the ID of the (a) rule named 'Always valid (Default)'.
     *
     * It is not guaranteed that this rule exists and also not guaranteed that there is only one rule with this name.
     */
    public function getDefaultRuleId(): ?string
    {
        if (!$this->connection) {
            return $this->getDefaultRuleIdWithEntityManager();
        }

        /** @var string|false $id */
        $id = $this->connection->fetchOne(
            'SELECT LOWER(HEX(`id`)) FROM `rule`
            WHERE `name` = :name
            ORDER BY `created_at` ASC
            LIMIT 1',
            ['name' => self::DEFAULT_RULE_NAME],
        );

        if ($id === false) {
            throw new RuntimeException(sprintf('No rule found (default) name "%s".', self::DEFAULT_RULE_NAME));
        }

        return $id;
    }

    private function getDefaultRuleIdWithEntityManager(): ?string
    {
        /** @var null|RuleEntity $defaultRule */
        $defaultRule = $this->entityManager->findFirstBy(
            RuleDefinition::class,
            new FieldSorting('createdAt', FieldSorting::ASCENDING),
            $this->context,
            ['name' => self::DEFAULT_RULE_NAME],
        );

        return $defaultRule ? $defaultRule->getId() : null;
    }

    public function resolveIdForStateMachine(string $technicalName): string
    {
        if (!$this->connection) {
            return $this->resolveIdForStateMachineWithEntityManager($technicalName);
        }

        /** @var string|false $id */
        $id = $this->connection->fetchOne(
            'SELECT LOWER(HEX(`id`)) FROM `state_machine` WHERE `technical_name` = :technicalName LIMIT 1',
            ['technicalName' => $technicalName],
        );
        if ($id === false) {
            throw new RuntimeException(sprintf('No state machine found for technical name "%s".', $technicalName));
        }

        return $id;
    }

    private function resolveIdForStateMachineWithEntityManager(string $technicalName): string
    {
        return $this->entityManager
            ->getOneBy(
                StateMachineDefinition::class,
                [
                    'technicalName' => $technicalName,
                ],
                $this->context,
            )
            ->getId();
    }
}
