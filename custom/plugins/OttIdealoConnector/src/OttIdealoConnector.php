<?php declare(strict_types=1);

namespace Ott\IdealoConnector;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class OttIdealoConnector extends Plugin
{
    public const TITLE_IDEALO_PAYMENT = 'IDEALO_CHECKOUT_PAYMENTS';
    private EntityRepository $customFieldSetRepository;
    private EntityRepository $customFieldRepository;
    private array $fieldSets = [];
    private array $fields = [];

    public function install(InstallContext $context): void
    {
        $this->createCustomField($context);

        if (!$this->hasIdealoPayment()) {
            $this->createIdealoPayment();
        }
    }

    public function update(UpdateContext $updateContext): void
    {
        $this->createCustomField($updateContext);
    }

    private function createCustomField($context): void
    {
        $this->customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $this->customFieldRepository = $this->container->get('custom_field.repository');

        $this->addFieldSet(
            'idealo',
            [
                'de-DE' => 'Idealo',
                'en-GB' => 'Idealo',
            ],
            [
                [
                    'entityName' => 'OrderTransaction',
                ],
            ]
        );

        $this->addField('idealo', [
            'name'   => 'transaction_id',
            'type'   => CustomFieldTypes::TEXT,
            'config' => [
                'label' => [
                    'de-DE' => 'TransaktionsID',
                    'en-GB' => 'TransactionID',
                ],
                'componentName'   => 'sw-text-editor',
                'customFieldType' => 'textEditor',
            ],
        ]);

        $this->updateCustomFields($context);
    }

    private function hasIdealoPayment(): bool
    {
        $result = $this->container->get('payment_method.repository')->search(
            (new Criteria())->addFilter(new EqualsFilter('name', self::TITLE_IDEALO_PAYMENT)),
            Context::createDefaultContext()
        );

        return 0 < $result->getTotal();
    }

    private function createIdealoPayment(): void
    {
        $this->container->get('payment_method.repository')->create([[
            'name'     => self::TITLE_IDEALO_PAYMENT,
            'position' => 100,
        ]], Context::createDefaultContext());
    }

    private function addFieldSet(string $name, array $labels = [], array $relations = []): void
    {
        $this->fieldSets[] = ['name' => $name, 'labels' => $labels, 'relations' => $relations];
    }

    private function addField(string $fieldSetName, array $field): void
    {
        if (!isset($this->fields[$fieldSetName])) {
            $this->fields[$fieldSetName] = [];
        }

        $this->fields[$fieldSetName][] = $field;
    }

    private function updateCustomFields($context): void
    {
        $fieldSets = [];
        foreach ($this->fieldSets as $fieldSet) {
            $fields = [];
            foreach ($this->fields[$fieldSet['name']] as $field) {
                $field['name'] = sprintf('custom_%s_%s', $fieldSet['name'], $field['name']);

                $criteria = new Criteria();
                $criteria->addFilter(new EqualsFilter('name', $field['name']))
                    ->setLimit(1)
                    ->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_NONE)
                ;

                $result = $this->customFieldRepository->search($criteria, Context::createDefaultContext());
                if (0 < $result->getTotal()) {
                    continue;
                }

                $fields[] = $field;
            }

            $fieldSetData = [
                'name'         => $fieldSet['name'],
                'config'       => [
                    'label' => $fieldSet['labels'],
                ],
                'customFields' => $fields,
                'relations'    => $fieldSet['relations'],
            ];

            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('name', $fieldSet['name']));
            $criteria->setLimit(1);

            $result = $this->customFieldSetRepository->search($criteria, Context::createDefaultContext());

            if (0 < $result->getTotal()) {
                $entity = $result->getEntities()->first();
                $fieldSetData['id'] = $entity->getId();
                unset($fieldSetData['relations']);
            }

            $fieldSets[] = $fieldSetData;
        }

        $this->customFieldSetRepository->upsert($fieldSets, $context->getContext());
    }
}
