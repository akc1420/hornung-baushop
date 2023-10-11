import VariantSwitchPlugin from 'src/plugin/variant-switch/variant-switch.plugin';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';

export const SWAG_CMS_EXTENSIONS_VARIANT_SWITCH_EXTENSION = {
    EVENT: {
        VARIANT_SWITCHED: 'SwagCmsExtensionsVariantSwitchPluginVariantSwitched',
    },
};

export default class SwagCmsExtensionsVariantSwitchExtension extends VariantSwitchPlugin {
    static options = {
        parentId: '',
        listingProductId: '',
        ...super.options,
    };

    /**
     * _onChange wraps the original method and fires an event instead of executing it,
     * when the variant switch form is shown inside a quickview.
     *
     * @param {Object} event
     */
    _onChange(event) {
        const idAttributeSelector = 'data-swag-cms-extensions-quickview-carousel-product-id';
        const carouselSelector = `[${idAttributeSelector}="${this.options.listingProductId}"]`;

        if (this._contains(this.el, carouselSelector)) {
            const buyBox = document.querySelector(`${carouselSelector} .product-detail-buy`).firstElementChild;

            ElementLoadingIndicatorUtil.create(buyBox);
            this._emitVariantSwitchEvent(event, this.$emitter);
        } else {
            super._onChange(event);
        }
    }

    /**
     * _emitVariantSwitchEvent will publish an event containing all necessary data for
     * fetching a variant quickview.
     * The options are pre-processed using JSON-stringify to simplify the controller call
     * like it's done in the parent plugin.
     *
     * @param {event} originalEvent
     * @param {NativeEventEmitter} emitter
     *
     * @returns {void}
     */
    _emitVariantSwitchEvent(originalEvent, emitter) {
        emitter.publish(SWAG_CMS_EXTENSIONS_VARIANT_SWITCH_EXTENSION.EVENT.VARIANT_SWITCHED, {
            parentId: this.options.parentId,
            listingProductId: this.options.listingProductId,
            switched: this._getSwitchedOptionId(originalEvent.target),
            options: JSON.stringify(this._getFormValue()),
        });
    }

    /**
     * _contains checks if an element is contained inside any of the parent elements selected
     * via parentSelector.
     *
     * @param {HTMLElement|EventTarget} element
     * @param {string} parentSelector
     *
     * @returns {boolean}
     */
    _contains(element, parentSelector) {
        return [...document.querySelectorAll(parentSelector)]
            .map((parent) => parent.contains(element))
            .reduce((acc, contained) => !!acc || contained, false);
    }
}
