<?php declare(strict_types=1);

namespace TcinnThemeWareModern\CustomFields;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class CustomFieldUpdater
{
    private EntityRepositoryInterface $customFieldSetRepository;

    private EntityRepositoryInterface $customFieldRepository;

    private string $pluginPath;

    public function __construct(
        EntityRepositoryInterface $customFieldSetRepository,
        EntityRepositoryInterface $customFieldRepository,
        $pluginPath
    ) {
        $this->customFieldSetRepository = $customFieldSetRepository;
        $this->customFieldRepository = $customFieldRepository;
        $this->pluginPath = $pluginPath;
    }

    public function remove()
    {
        $criteria = (new Criteria())
            ->addFilter(new ContainsFilter('name', 'twt_modern_pro_custom_field__'));

        $customFields = $this->customFieldRepository->search($criteria, Context::createDefaultContext());

        if($customFields) {
            foreach ($customFields as $customField) {
                $this->removeCustomField($customField->getId());
            };
        }

        $criteria = (new Criteria())
            ->addFilter(new ContainsFilter('name', 'twt_modern_pro_custom_field_set__'));

        $customFieldSets = $this->customFieldSetRepository->search($criteria, Context::createDefaultContext());

        foreach ($customFieldSets as $customFieldSet) {
            $this->removeCustomFieldset($customFieldSet->getId());
        }
    }

    public function sync()
    {
        $this->deleteRemoved();

        foreach ($this->getCustomFieldSetStorage() as $customFieldSetData) {
            $customFieldSet = $this->getCustomFieldSet($customFieldSetData['name']);

            if (!$customFieldSet) {
                $this->createCustomFieldset($customFieldSetData);
            }

            foreach ($customFieldSetData['customFields'] as $customFieldData) {
                if (!$this->getCustomField($customFieldSetData['name'], $customFieldData['name'])) {
                    $this->createCustomField($customFieldSetData['name'], $customFieldData);
                }
            }
        }
    }

    private function createCustomField($setName, $customField)
    {
        if (
            !$customField['name'] ||
            !$customField['config']
        ) {
            return;
        }

        $customFieldSet = $this->getCustomFieldSet($setName);

        if (!$customFieldSet) {
            return;
        }

        $this->customFieldRepository->create([
            [
                'id' => Uuid::randomHex(),
                'name' => $customField['name'],
                'type' => $customField['type'],
                'config' => $customField['config'],
                'customFieldSetId' => $customFieldSet->getId()
            ]
        ], Context::createDefaultContext());
    }

    private function createCustomFieldset($customFieldSet)
    {
        if (
            !$customFieldSet['name'] ||
            !$customFieldSet['config'] ||
            !$customFieldSet['relations']
        ) {
            return;
        }

        $this->customFieldSetRepository->create([
            [
                'id' => Uuid::randomHex(),
                'name' => $customFieldSet['name'],
                'config' => $customFieldSet['config'],
                'relations' => $customFieldSet['relations']
            ]
        ], Context::createDefaultContext());
    }

    private function deleteRemoved()
    {
        $customFieldSetStorage['field_sets'] = [];
        $customFieldSetStorage['fields'] = [];

        foreach ($this->getCustomFieldSetStorage() as $customFieldSetData) {
            $customFieldSetStorage['field_sets'][] = $customFieldSetData['name'];

            $customFieldSetStorage['fields'] = array_merge(
                $customFieldSetStorage['fields'],
                $customFieldSetData['customFields']
            );
        }

        $criteria = (new Criteria())
            ->addFilter(new ContainsFilter('name', 'twt_modern_pro_custom_field__'));

        $customFields = $this->customFieldRepository->search($criteria, Context::createDefaultContext());

        if($customFields) {
            foreach ($customFields as $customField) {
                if(in_array($customField->getName(), $customFieldSetStorage['fields'])) {
                    continue;
                }
                $this->removeCustomField($customField->getId());
            };
        }

        $criteria = (new Criteria())
            ->addFilter(new ContainsFilter('name', 'twt_modern_pro_custom_field_set__'));

        $customFieldSets = $this->customFieldSetRepository->search($criteria, Context::createDefaultContext());

        if($customFieldSets) {
            foreach ($customFieldSets as $customFieldSet) {
                if(in_array($customFieldSet->getName(), $customFieldSetStorage['field_sets'])) {
                    continue;
                }
                $this->removeCustomFieldset($customFieldSet->getId());
            }
        }
    }

    private function getCustomField($setName, $fieldName)
    {
        if (!$setName || !$fieldName) {
            return;
        }

        $customFieldSet = $this->getCustomFieldSet($setName);

        if (!$customFieldSet) {
            return;
        }

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('name', $fieldName))
            ->addFilter(new EqualsFilter('customFieldSetId', $customFieldSet->getId()));

        return $this->customFieldRepository
            ->search($criteria, Context::createDefaultContext())
            ->first();
    }

    private function getCustomFieldSet($name)
    {
        if (!$name) {
            return;
        }

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('name', $name));

        $result = $this->customFieldSetRepository->search(
            $criteria, Context::createDefaultContext()
        );

        return $result->first();
    }

    private function getCustomFieldSetStorage()
    {
        $customFieldSetStorage = [];

        $customFieldSetStorageFiles = glob(
            "{$this->pluginPath}/CustomFields/CustomFieldSets/twt_*.json"
        );

        if($customFieldSetStorageFiles) {
            foreach ($customFieldSetStorageFiles as $customFieldSetFile) {
                $customFieldSetStorage[] = json_decode(file_get_contents($customFieldSetFile), true);
            }
        }

        return $customFieldSetStorage;
    }

    private function removeCustomField($id)
    {
        $this->customFieldRepository->delete([
            ['id' => $id]
        ], Context::createDefaultContext());
    }

    private function removeCustomFieldset($id)
    {
        $this->customFieldSetRepository->delete([
            ['id' => $id]
        ], Context::createDefaultContext());
    }
}
