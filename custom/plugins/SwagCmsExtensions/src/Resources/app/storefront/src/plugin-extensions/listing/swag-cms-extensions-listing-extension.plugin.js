import ListingPlugin from 'src/plugin/listing/listing.plugin';

export const SWAG_CMS_EXTENSIONS_LISTING_EXTENSION = {
    EVENT: {
        RENDER_RESPONSE: 'SwagCmsExtensionsListingPluginRenderResponse',
    },
};

export default class SwagCmsExtensionsListingExtension extends ListingPlugin {
    renderResponse(response) {
        super.renderResponse(response);

        this.$emitter.publish(SWAG_CMS_EXTENSIONS_LISTING_EXTENSION.EVENT.RENDER_RESPONSE);
    }
}
