<?php

declare(strict_types=1);

namespace Sisi\Search\Service;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\Framework\Context;

class CustomFieldsService
{
    /**
     * @var EntityRepositoryInterface
     */
    private $customFieldSetRepository;

    public function __construct(EntityRepositoryInterface $customFieldSetRepository)
    {
        $this->customFieldSetRepository = $customFieldSetRepository;
    }

    /**
     * @param context $context
     */
    public function setFieds(context $context): void
    {
        $this->customFieldSetRepository->create(
            [
                [
                    'name' => 'sisi_test',
                    'customFields' => [
                        ['name' => 'sisi_test_size', 'type' => CustomFieldTypes::INT],

                    ]
                ]
            ],
            $context
        );
    }
}
