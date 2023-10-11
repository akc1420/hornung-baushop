<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Component\Validation;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use SwagSocialShopping\Component\Validation\Validator\ProductImageValidator;
use SwagSocialShopping\DataAbstractionLayer\Entity\SocialShoppingSalesChannelEntity;

class NetworkProductValidator
{
    public const VALIDATION_CONTEXT_VALIDATION = 'validation';
    public const VALIDATION_CONTEXT_RENDERER = 'renderer';

    private \IteratorAggregate $validators;

    private EntityRepositoryInterface $socialShoppingErrorRepository;

    public function __construct(\IteratorAggregate $validators, EntityRepositoryInterface $socialShoppingErrorRepository)
    {
        $this->validators = $validators;
        $this->socialShoppingErrorRepository = $socialShoppingErrorRepository;
    }

    /**
     * @param string $validationContext allows skipping some validators depending on context
     */
    public function executeValidators(
        EntityCollection $productCollection,
        SocialShoppingSalesChannelEntity $socialShoppingSalesChannelEntity,
        bool $clearErrors = true,
        string $validationContext = self::VALIDATION_CONTEXT_VALIDATION
    ): bool {
        $context = Context::createDefaultContext();
        $hasErrors = false;

        if ($clearErrors) {
            $this->clearErrors($socialShoppingSalesChannelEntity->getSalesChannelId(), $context);
        }

        $configuration = $socialShoppingSalesChannelEntity->getConfiguration();
        if ($configuration === null || !isset($configuration['includeVariants'])) {
            $includeVariants = false;
        } else {
            $includeVariants = $configuration['includeVariants'];
        }

        foreach ($this->validators as $validator) {
            if (!$validator->supports($socialShoppingSalesChannelEntity->getNetwork())) {
                continue;
            }
            //the export of products should not fail because one has a missing picture, but validation should still report this
            if ($validationContext === self::VALIDATION_CONTEXT_RENDERER && \get_class($validator) === ProductImageValidator::class) {
                continue;
            }

            foreach ($productCollection->getElements() as $productEntity) {
                if ($includeVariants && !$productEntity->getParentId() && $productEntity->getChildCount() > 0) {
                    continue; // Skip main product if variants are included
                }
                if (!$includeVariants && $productEntity->getParentId()) {
                    continue; // Skip variants unless they are included
                }
                $validationResult = $validator->validate($productEntity, $socialShoppingSalesChannelEntity);

                if ($validationResult->hasErrors()) {
                    $this->writeError($validationResult, $productEntity, $socialShoppingSalesChannelEntity, $context);
                    $hasErrors = true;
                }
            }
        }

        return $hasErrors;
    }

    public function clearErrors(string $socialShoppingSalesChannelId, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('salesChannelId', $socialShoppingSalesChannelId)
        );

        $ids = $this->socialShoppingErrorRepository->searchIds($criteria, $context);

        if (\count($ids->getData()) === 0) {
            return;
        }

        $this->socialShoppingErrorRepository->delete(\array_values($ids->getData()), $context);
    }

    private function writeError(NetworkProductValidationResult $result, ProductEntity $productEntity, SocialShoppingSalesChannelEntity $socialShoppingSalesChannelEntity, Context $context): void
    {
        $this->socialShoppingErrorRepository->create(
            [
                [
                    'productId' => $productEntity->getId(),
                    'productVersionId' => $productEntity->getVersionId(),
                    'salesChannelId' => $socialShoppingSalesChannelEntity->getSalesChannelId(),
                    'errors' => $result->getErrors()->jsonSerialize(),
                ],
            ],
            $context
        );
    }
}
