<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Installer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSetRelation\CustomFieldSetRelationCollection;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSetRelation\CustomFieldSetRelationEntity;
use Shopware\Core\System\CustomField\CustomFieldCollection;
use Shopware\Core\System\CustomField\CustomFieldEntity;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class CustomFieldInstaller implements InstallerInterface
{
    public const SOCIAL_SHOPPING_CUSTOM_FIELD_SET_NAME = 'custom_SwagSocialShopping';
    public const SOCIAL_SHOPPING_CUSTOM_FIELD_GOOGLE_CATEGORY_NAME = 'swag_social_shopping_google_category';
    public const SOCIAL_SHOPPING_CUSTOM_FIELD_GOOGLE_CATEGORY = [
        'name' => self::SOCIAL_SHOPPING_CUSTOM_FIELD_GOOGLE_CATEGORY_NAME,
        'type' => CustomFieldTypes::TEXT,
        'config' => [
            'label' => [
                'en-GB' => 'Google Product Category',
                'de-DE' => 'Google Produktkategorie',
            ],
        ],
    ];
    private const SOCIAL_SHOPPING_CUSTOM_FIELD_SET = [
        'name' => self::SOCIAL_SHOPPING_CUSTOM_FIELD_SET_NAME,
        'config' => [
            'label' => [
                'en-GB' => 'Social Shopping',
                'de-DE' => 'Social Shopping',
            ],
        ],
    ];
    private const SOCIAL_SHOPPING_CUSTOM_FIELD_SET_RELATION_ENTITY = 'category';
    private const SOCIAL_SHOPPING_CUSTOM_FIELD_SET_RELATION = [
        'entityName' => self::SOCIAL_SHOPPING_CUSTOM_FIELD_SET_RELATION_ENTITY,
    ];

    private EntityRepositoryInterface $customFieldSetRepository;

    public function __construct(EntityRepositoryInterface $customFieldSetRepository)
    {
        $this->customFieldSetRepository = $customFieldSetRepository;
    }

    public function install(InstallContext $context): void
    {
        $this->upsertCustomFields($context->getContext());
    }

    public function update(UpdateContext $context): void
    {
        $this->upsertCustomFields($context->getContext());
    }

    public function uninstall(UninstallContext $context): void
    {
        if (!$context->keepUserData()) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('name', self::SOCIAL_SHOPPING_CUSTOM_FIELD_SET_NAME));

            $customFieldSetIds = $this->customFieldSetRepository->searchIds($criteria, $context->getContext());
            if ($customFieldSetIds->getTotal() === 0) {
                return;
            }

            $customFieldSetIds = \array_map(static function ($id) {
                return ['id' => $id];
            }, $customFieldSetIds->getIds());
            $this->customFieldSetRepository->delete($customFieldSetIds, $context->getContext());
        }
    }

    public function activate(ActivateContext $context): void
    {
    }

    public function deactivate(DeactivateContext $context): void
    {
    }

    private function upsertCustomFields(Context $context): void
    {
        $upsertDataSet = self::SOCIAL_SHOPPING_CUSTOM_FIELD_SET;

        $customFieldSet = $this->getCustomFieldSet($context);
        if ($customFieldSet === null) {
            $upsertDataSet['customFields'] = [self::SOCIAL_SHOPPING_CUSTOM_FIELD_GOOGLE_CATEGORY];
            $upsertDataSet['relations'] = [self::SOCIAL_SHOPPING_CUSTOM_FIELD_SET_RELATION];

            $this->customFieldSetRepository->upsert([$upsertDataSet], $context);

            return;
        }

        $upsertDataSet['id'] = $customFieldSet->getId();

        $upsertDataField = $this->checkForExistingCustomField(
            $customFieldSet->getCustomFields(),
            static::SOCIAL_SHOPPING_CUSTOM_FIELD_GOOGLE_CATEGORY
        );
        $upsertDataRelation = $this->checkForExistingRelation(
            $customFieldSet->getRelations(),
            self::SOCIAL_SHOPPING_CUSTOM_FIELD_SET_RELATION
        );

        $upsertDataSet['customFields'] = [$upsertDataField];
        $upsertDataSet['relations'] = [$upsertDataRelation];

        $this->customFieldSetRepository->upsert([$upsertDataSet], $context);
    }

    private function checkForExistingCustomField(?CustomFieldCollection $customFields, array $upsertDataField): array
    {
        if ($customFields === null) {
            return $upsertDataField;
        }

        /** @var CustomFieldEntity|null $googleCategoryCustomField */
        $googleCategoryCustomField = $customFields->filterAndReduceByProperty(
            'name',
            self::SOCIAL_SHOPPING_CUSTOM_FIELD_GOOGLE_CATEGORY_NAME
        )->first();

        if ($googleCategoryCustomField !== null) {
            $upsertDataField['id'] = $googleCategoryCustomField->getId();
        }

        return $upsertDataField;
    }

    private function checkForExistingRelation(?CustomFieldSetRelationCollection $relations, array $upsertDataRelation): array
    {
        if ($relations === null) {
            return $upsertDataRelation;
        }

        /** @var CustomFieldSetRelationEntity|null $categoryRelation */
        $categoryRelation = $relations->filterAndReduceByProperty(
            'entityName',
            self::SOCIAL_SHOPPING_CUSTOM_FIELD_SET_RELATION_ENTITY
        )->first();

        if ($categoryRelation !== null) {
            $upsertDataRelation['id'] = $categoryRelation->getId();
        }

        return $upsertDataRelation;
    }

    private function getCustomFieldSet(Context $context): ?CustomFieldSetEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', self::SOCIAL_SHOPPING_CUSTOM_FIELD_SET_NAME));
        $criteria->addAssociation('customFields');
        $criteria->addAssociation('relations');

        /** @var CustomFieldSetEntity|null $customFieldSet */
        $customFieldSet = $this->customFieldSetRepository->search($criteria, $context)->first();

        return $customFieldSet;
    }
}
