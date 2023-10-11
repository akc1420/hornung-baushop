<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Util\Feature;

use PHPUnit\Framework\TestCase;
use Swag\CmsExtensions\Util\Feature\SwagCmsExtensionsFeatureUtil;

class SwagCmsExtensionsFeatureUtilTest extends TestCase
{
    /**
     * @var SwagCmsExtensionsFeatureUtil
     */
    private $featureUtil;

    protected function setUp(): void
    {
        $this->featureUtil = new SwagCmsExtensionsFeatureUtil();
        $this->featureUtil->registerFeatures();
    }

    public function testFeaturesAreRegisteredPerDefault(): void
    {
        static::assertTrue($this->featureUtil->cmsFeatureEnabled(SwagCmsExtensionsFeatureUtil::SWAG_CMS_EXTENSIONS_FEATURE_QUICKVIEW));
        static::assertTrue($this->featureUtil->cmsFeatureEnabled(SwagCmsExtensionsFeatureUtil::SWAG_CMS_EXTENSIONS_FEATURE_SCROLL_NAVIGATION));
        static::assertTrue($this->featureUtil->cmsFeatureEnabled(SwagCmsExtensionsFeatureUtil::SWAG_CMS_EXTENSIONS_FEATURE_BLOCK_RULE));
        static::assertTrue($this->featureUtil->cmsFeatureEnabled(SwagCmsExtensionsFeatureUtil::SWAG_CMS_EXTENSIONS_FEATURE_FORM_BUILDER));
    }

    public function testCmsFeatureEnabledReturnsFalseForNoneExistingFeature(): void
    {
        static::assertFalse($this->featureUtil->cmsFeatureEnabled('IM_DEFINITLY_NOT_EXISTING'));
    }

    public function testCmsFeatureEnabledReturnsFalseForDisabledFeature(): void
    {
        $_SERVER['FEATURE_SWAGCMSEXTENSIONS_63'] = '0';

        static::assertFalse($this->featureUtil->cmsFeatureEnabled(SwagCmsExtensionsFeatureUtil::SWAG_CMS_EXTENSIONS_FEATURE_FORM_BUILDER));

        $_SERVER['FEATURE_SWAGCMSEXTENSIONS_63'] = '1';
    }
}
