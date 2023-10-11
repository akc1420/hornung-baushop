import CrossSellingPlugin from 'src/plugin/cross-selling/cross-selling.plugin';

export const SWAG_CMS_EXTENSIONS_CROSS_SELLING_EXTENSION = {
    EVENT: {
        RENDER_RESPONSE: 'SwagCmsExtensionsCrossSellingPluginRenderResponse',
    },
};

export default class SwagCmsExtensionsCrossSellingExtension extends CrossSellingPlugin {
    _rebuildCrossSellingSlider(event) {
        super._rebuildCrossSellingSlider(event);

        this.$emitter.publish(SWAG_CMS_EXTENSIONS_CROSS_SELLING_EXTENSION.EVENT.RENDER_RESPONSE);
    }
}
