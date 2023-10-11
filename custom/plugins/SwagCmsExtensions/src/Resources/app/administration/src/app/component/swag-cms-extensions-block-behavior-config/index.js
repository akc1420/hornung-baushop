import template from './swag-cms-extensions-block-behavior-config.html.twig';

const { Component } = Shopware;
// @deprecated tag:v2.0.0 - Functionality moved to `swag-cms-extensions-block-config-quickview`
const { Application: { view: { setReactive } } } = Shopware;

Component.register('swag-cms-extensions-block-behavior-config', {
    template,

    // @deprecated tag:v2.0.0 - Functionality moved to `swag-cms-extensions-block-config-quickview`
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
        // @deprecated tag:v2.0.0 - Functionality moved to `swag-cms-extensions-block-config-quickview`
        productRelatedBlocks() {
            return [
                'product-listing',
                'product-slider',
                'product-three-column',
            ];
        },

        // @deprecated tag:v2.0.0 - Functionality moved to `swag-cms-extensions-block-config-quickview`
        productRelatedSlots() {
            return [
                'product-box',
                'product-slider',
            ];
        },

        // @deprecated tag:v2.0.0 - Functionality moved to `swag-cms-extensions-block-config-quickview`
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

        // @deprecated tag:v2.0.0 - Functionality moved to `swag-cms-extensions-block-config-quickview`
        quickviewRepository() {
            return this.repositoryFactory.create('swag_cms_extensions_quickview');
        },

        // @deprecated tag:v2.0.0 - Functionality moved to `swag-cms-extensions-block-config-quickview`
        quickviewExtensionDefined() {
            return this.block.extensions.swagCmsExtensionsQuickview !== undefined;
        },

        // @deprecated tag:v2.0.0 - Functionality moved to `swag-cms-extensions-block-config-quickview`
        quickview() {
            if (this.quickviewExtensionDefined) {
                return this.block.extensions.swagCmsExtensionsQuickview;
            }
            /* Prevents a superfluous call to create(), if the block cannot make use of the quickview */
            return this.blockIsProductRelated ? this.quickviewRepository.create(Shopware.Context.api) : {};
        },

        blockRuleFeatureActive() {
            return Shopware.Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_8');
        },

        quickviewFeatureActive() {
            return Shopware.Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_1');
        },
    },

    methods: {
        // @deprecated tag:v2.0.0 - Functionality moved to `swag-cms-extensions-block-config-quickview`
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
