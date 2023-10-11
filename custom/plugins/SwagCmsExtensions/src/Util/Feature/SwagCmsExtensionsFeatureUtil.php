<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Util\Feature;

use Shopware\Core\Framework\Feature;

class SwagCmsExtensionsFeatureUtil
{
    public const SWAG_CMS_EXTENSIONS_FEATURE_FORM_BUILDER = 'form_builder';
    public const SWAG_CMS_EXTENSIONS_FEATURE_QUICKVIEW = 'quickview';
    public const SWAG_CMS_EXTENSIONS_FEATURE_BLOCK_RULE = 'block_rule';
    public const SWAG_CMS_EXTENSIONS_FEATURE_SCROLL_NAVIGATION = 'scroll_navigation';

    private const FEATURE_PATTERN = 'FEATURE_SWAGCMSEXTENSIONS_%d';
    private const AVAILABLE_FEATURES = [
        self::SWAG_CMS_EXTENSIONS_FEATURE_QUICKVIEW => 1,
        self::SWAG_CMS_EXTENSIONS_FEATURE_SCROLL_NAVIGATION => 2,
        self::SWAG_CMS_EXTENSIONS_FEATURE_BLOCK_RULE => 8,
        self::SWAG_CMS_EXTENSIONS_FEATURE_FORM_BUILDER => 63,
    ];

    /**
     * Checks if a feature for SwagCmsExtensions is active.
     */
    public function cmsFeatureEnabled(string $feature): bool
    {
        if (!\array_key_exists($feature, self::AVAILABLE_FEATURES)) {
            return false;
        }

        return Feature::isActive(\sprintf(self::FEATURE_PATTERN, self::AVAILABLE_FEATURES[$feature]));
    }

    public function registerFeatures(): void
    {
        foreach (self::AVAILABLE_FEATURES as $featureName => $id) {
            Feature::registerFeature(\sprintf(self::FEATURE_PATTERN, $id), [
                'default' => true,
                'description' => $featureName,
            ]);
        }
    }
}
