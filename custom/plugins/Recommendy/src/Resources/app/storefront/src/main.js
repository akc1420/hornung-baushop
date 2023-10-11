import PluginManager from 'src/plugin-system/plugin.manager';

/* import plugins */
import GetRecommendyProductsPlugin from './plugin/get-recommendy-products/get-recommendy-products.plugin';
import RecommendyTrackingPlugin from './plugin/recommendy-tracking/recommendy-tracking.plugin';
import RecommendyVariantSwitchPlugin from './plugin/recommendy-variant/recommendy-variant.plugin';

/* register plugins */
PluginManager.register('GetRecommendyProducts', GetRecommendyProductsPlugin, '[data-get-recommendy-products]');
PluginManager.register('RecommendyTracking', RecommendyTrackingPlugin, 'body');
PluginManager.register('RecommendyTracking', RecommendyTrackingPlugin, '.offcanvas-cart');
PluginManager.register('RecommendyVariantSwitchPlugin', RecommendyVariantSwitchPlugin,'[data-recommendy-ajax-variants-container]');
