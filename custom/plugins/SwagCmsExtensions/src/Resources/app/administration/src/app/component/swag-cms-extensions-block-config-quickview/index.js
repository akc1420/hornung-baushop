import template from './swag-cms-extensions-block-config-quickview.html.twig';
import './swag-cms-extensions-block-config-quickview.scss';

const { Component, Application: { view: { setReactive } } } = Shopware;

Component.register('swag-cms-extensions-block-config-quickview', {
    template,

    inject: [
        'repositoryFactory',
        'acl',
    ],

    model: {
        prop: 'block',
        event: 'block-update',
    },

    props: {
        block: {
            type: Object,
            required: true,
            default() {
                return {};
            },
        },
    },

    computed: {
        productRelatedBlocks() {
            return [
                'product-listing',
                'product-slider',
                'product-three-column',
                'cross-selling',
            ];
        },

        productRelatedSlots() {
            return [
                'product-box',
                'product-slider',
            ];
        },

        blockIsProductRelated() {
            // Check if one of the slots is a product related slot
            const productRelatedSlot = this.block.slots.reduce((accumulator, slot) => {
                if (accumulator) {
                    return accumulator;
                }

                accumulator = this.productRelatedSlots.includes(slot.type);

                return accumulator;
            }, false);

            return (productRelatedSlot || this.productRelatedBlocks.includes(this.block.type));
        },

        quickviewRepository() {
            return this.repositoryFactory.create('swag_cms_extensions_quickview');
        },

        quickviewExtensionDefined() {
            return this.block.extensions.swagCmsExtensionsQuickview !== undefined;
        },

        quickview() {
            if (this.quickviewExtensionDefined) {
                return this.block.extensions.swagCmsExtensionsQuickview;
            }
            /* Prevents a superfluous call to create(), if the block cannot make use of the quickview */
            return this.blockIsProductRelated ? this.quickviewRepository.create(Shopware.Context.api) : {};
        },
    },

    methods: {
        quickviewActiveChanged(state) {
            if (!this.quickviewExtensionDefined) {
                /**
                 * This will be executed, when the user activates the quickview for the first
                 * time and is necessary to prepare the extension to be saved later on.
                 */
                setReactive(this.block.extensions, 'swagCmsExtensionsQuickview', this.quickview);
                setReactive(this.block.extensions.swagCmsExtensionsQuickview, 'active', state);
            }
        },
    },
});
