<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Service\Content\Cms\SalesChannel;

use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSection\CmsSectionEntity;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Cms\SalesChannel\SalesChannelCmsPageLoaderInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\CmsExtensions\Extension\Feature\BlockRule\CmsBlockEntityExtension;
use Symfony\Component\HttpFoundation\Request;

class SalesChannelCmsPageLoaderBlockRuleDecorator implements SalesChannelCmsPageLoaderInterface
{
    public const BLOCK_RULE_ASSOCIATION_PATH = 'sections.blocks.' . CmsBlockEntityExtension::BLOCK_RULE_ASSOCIATION_PROPERTY_NAME;
    public const BLOCK_RULE_VISIBILITY_RULE_ASSOCIATION_PATH = self::BLOCK_RULE_ASSOCIATION_PATH . '.visibilityRule';

    private SalesChannelCmsPageLoaderInterface $inner;

    public function __construct(SalesChannelCmsPageLoaderInterface $inner)
    {
        $this->inner = $inner;
    }

    /**
     * @param array<string, mixed>|null $config
     */
    public function load(
        Request $request,
        Criteria $criteria,
        SalesChannelContext $context,
        ?array $config = null,
        ?ResolverContext $resolverContext = null
    ): EntitySearchResult {
        $pages = $this->inner->load(
            $request,
            $this->addBlockRuleAssociations($criteria),
            $context,
            $config,
            $resolverContext
        );

        /** @var CmsPageEntity $page */
        foreach ($pages as $page) {
            $sections = $page->getSections();

            if ($sections === null || \count($sections) === 0) {
                continue;
            }

            foreach ($sections as $section) {
                $blocks = $section->getBlocks();
                if ($blocks === null || \count($blocks) === 0) {
                    continue;
                }

                $filteredBlocks = $blocks->filter(function (CmsBlockEntity $entity) use ($context) {
                    /** @var ArrayEntity|null $blockRule */
                    $blockRule = $entity->getExtension('swagCmsExtensionsBlockRule');

                    return $this->getBlockVisibility($blockRule, $context);
                });

                $section->setBlocks($filteredBlocks);
            }

            $filteredSections = $sections->filter(static function (CmsSectionEntity $entity) {
                $blocks = $entity->getBlocks();

                return $blocks !== null && \count($blocks) > 0;
            });

            $page->setSections($filteredSections);
        }

        return $pages;
    }

    protected function addBlockRuleAssociations(Criteria $criteria): Criteria
    {
        return $criteria
            ->addAssociation(self::BLOCK_RULE_VISIBILITY_RULE_ASSOCIATION_PATH);
    }

    private function getBlockVisibility(?ArrayEntity $blockRule, SalesChannelContext $context): bool
    {
        if ($blockRule === null) {
            return true;
        }

        $visibilityRuleId = $blockRule->get('visibilityRuleId');
        $visibilityByRule = $visibilityRuleId !== null ? \in_array($visibilityRuleId, $context->getRuleIds(), true) : true;

        return $this->applyInverted($blockRule, $visibilityByRule);
    }

    private function applyInverted(ArrayEntity $blockRule, bool $visibilityByRule): bool
    {
        return $visibilityByRule !== $blockRule->get('inverted');
    }
}
