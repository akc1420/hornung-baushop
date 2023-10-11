<?php declare(strict_types=1);

namespace dev24\ExtendedVariants;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class dev24ExtendedVariants extends Plugin
{

    public function install(InstallContext $installContext): void
    {
        /** @var EntityRepositoryInterface $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field.repository');

        $customFields = [
            [
                'name' => 'dev24_variants_property_group_display_type',
                'type' => CustomFieldTypes::SELECT,
                'config' => [
                    'label' => [
                        'de-DE' => 'Darstellung der Variantenauswahl im Storefront',
                        'en-GB' => 'Display of the variant selection in the Storefront'
                    ],
                    'componentName' => "sw-single-select",
                    'customFieldType' => "select",
                    'options' => [
                        0 => [
                            'label' => [
                                'en-GB' => 'Auto'
                            ],
                            'value' => 'auto'
                        ],
                        1 => [
                            'label' => [
                                'en-GB' => 'Select'
                            ],
                            'value' => 'select'
                        ]
                    ],
                    'placeholder' => [
                        'en-GB' => 'Choose...'
                    ],
                ]
            ]
        ];

        $customFieldRepository->upsert($customFields, $installContext->getContext());

    }

    public function uninstall(UninstallContext $uninstallContext): void
    {

        if($uninstallContext->keepUserData()) {
            return;
        }

        $criteria = new Criteria();

        $criteria->addFilter(
            new EqualsFilter('name', 'dev24_variants_property_group_display_type')
        );

        /** @var EntityRepositoryInterface $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field.repository');
        $idsToDelete = $customFieldRepository->searchIds($criteria, $uninstallContext->getContext());

        if ($idsToDelete->getTotal() === 0) {
            return;
        }

        $keys = array_map(function ($id) {
            return ['id' => $id];
        }, $idsToDelete->getIds());

        if($idsToDelete->getTotal() > 0) {
            $customFieldRepository->delete($keys, $uninstallContext->getContext());
        }

    }

}
