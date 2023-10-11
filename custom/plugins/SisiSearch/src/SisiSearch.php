<?php

declare(strict_types=1);

namespace Sisi\Search;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class SisiSearch extends Plugin
{

    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        $this->createAttributes($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);
        $this->removeAttributes($uninstallContext);
    }


    private function createAttributes(InstallContext $installContext): void
    {
        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get('custom_field_set.repository');
        $context = $installContext->getContext();
        if ($this->isExistField("sisi_search_time", $context) == null) {
            $repository->create(
                [
                    [
                        'name' => 'sisi_search_time',
                        'customFields' => [
                            ['name' => 'sisi_search_time', 'type' => CustomFieldTypes::INT],

                        ],
                        'relations' => [
                            [
                                'entityName' => 'products',
                            ],
                        ],

                    ]
                ],
                $context
            );
        }

        if ($this->isExistField("sisi_list", $context) == null) {
            $repository->create(
                [
                    [
                        'name' => 'sisi_list',
                        'config' => [
                            'label' => [
                                'en-GB' => 'Display the product as list by SisiSearch',
                                'de-DE' => 'Produkt gefächert darstellen bei SisiSearch'
                            ]
                        ],
                        'customFields' => [
                            [
                                'name' => 'sisi_list',
                                'type' => CustomFieldTypes::INT,
                                'config' => [
                                    'en-GB' => 'Display the product as list by SisiSearch',
                                    'de-DE' => 'Produkt gefächert darstellen bei SisiSearch'
                                ],
                                'customFieldPosition' => 1

                            ],

                        ],
                        'relations' => [
                            [
                                'entityName' => 'product',
                            ],
                        ],
                    ],
                ],
                $context
            );
        }
    }

    private function removeAttributes(UninstallContext $uninstallContext): void
    {
        if (!$uninstallContext->keepUserData()) {
            $context = $uninstallContext->getContext();
            /** @var EntityRepositoryInterface $repository */
            $repository = $this->container->get('custom_field_set.repository');
            $userId = $this->isExistField("sisi_search_time", $context);
            if ($userId) {
                $repository->delete(
                    [
                        [
                            'id' => $userId
                        ]
                    ],
                    $uninstallContext->getContext()
                );
            }
            $userId2 = $this->isExistField("sisi_list", $context);
            if ($userId2) {
                $repository->delete(
                    [
                        [
                            'id' => $userId
                        ]
                    ],
                    $uninstallContext->getContext()
                );
            }
        }
    }

    /**
     * @param string $fieldName
     * @param context $context
     * @return bool|string
     */
    private function isExistField($fieldName, $context)
    {
        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get('custom_field_set.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $fieldName));
        $mittwaldFieldSetResponse = $repository->search(
            $criteria,
            $context
        );
        $fieldId = $mittwaldFieldSetResponse->getEntities()->getIds();
        $fieldId = reset($fieldId);
        return $fieldId;
    }
}
