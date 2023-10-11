import template from './sw-cms-section-actions.html.twig';

const { Component } = Shopware;

if (Shopware.Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_8')) {
    Component.override('sw-cms-section-actions', {
        template,

        computed: {
            hasRulesOnAllBlocks() {
                return this.section.blocks.every((block) => {
                    const rule = block.extensions.swagCmsExtensionsBlockRule;

                    return rule && (rule.inverted || !!rule.visibilityRuleId);
                });
            },

            rulesOnAllBlocksTooltip() {
                return {
                    message: this.$tc('swag-cms-extensions.sw-cms.sidebar.rulesOnAllBlocks'),
                    position: 'right',
                };
            },
        },
    });
}
