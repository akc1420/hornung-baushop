<?php declare(strict_types=1);

namespace phpschmied\CustomersAlsoBought;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class phpschmiedCustomersAlsoBought extends Plugin {

    private const CUSTOM_FIELD_SET_NAME = 'phps_customer_also_bought';

    /**
     * @param InstallContext $installContext
     */
    public function install(InstallContext $installContext): void {
        $context = $installContext->getContext();

        if (null === $this->getCustomFieldSet($context)) {
            $this->addCustomFields($context);
        }
    }

    /**
     * @param UninstallContext $uninstallContext
     */
    public function uninstall(Plugin\Context\UninstallContext $uninstallContext): void {
        $context = $uninstallContext->getContext();
        $customFieldSet = $this->getCustomFieldSet($context);

        if (false === $uninstallContext->keepUserData() && null !== $customFieldSet) {
            $this->getCustomFieldSetRepository()->delete([['id' => $customFieldSet->getId()]], $context);
        }
    }

    /**
     * @param Context $context
     */
    private function addCustomFields(Context $context): void {
        $this->getCustomFieldSetRepository()->create([
            [
                'name' => static::CUSTOM_FIELD_SET_NAME,
                'config' => [
                    'label' => [
                        'de-DE' => 'Kunden kauften auch nicht anzeigen',
                        'en-GB' => 'Do not show customers also bought',
                    ],
                ],
                'customFields' => [
                    [
                        'name' => 'show_customer_also_bought',
                        'type' => CustomFieldTypes::BOOL,
                        'config' => [
                            'label' => [
                                'de-DE' => 'Kunden kauften auch nicht anzeigen',
                                'en-GB' => 'Do not show customers also bought',
                            ],
                        ],
                    ]
                ],
                'relations' => [
                    [
                        'entityName' => ProductDefinition::ENTITY_NAME,
                    ]
                ],
            ]
        ], $context);
    }

    /**
     * @param Context $context
     * @return CustomFieldSetEntity|null
     */
    private function getCustomFieldSet(Context $context): ?CustomFieldSetEntity {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('custom_field_set.name', static::CUSTOM_FIELD_SET_NAME));

        return $this->getCustomFieldSetRepository()->search($criteria, $context)->first();
    }

    /**
     * @return EntityRepositoryInterface
     */
    private function getCustomFieldSetRepository(): EntityRepositoryInterface {
        return $this->container->get('custom_field_set.repository');
    }
}
