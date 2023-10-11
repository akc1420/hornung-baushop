import template from './sw-cms-section-config.html.twig';

const { Component } = Shopware;

if (Shopware.Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_8')) {
    Component.override('sw-cms-section-config', {
        template,

        computed: {
            hasRulesOnAllBlocks() {
                return this.section.blocks.every((block) => {
                    const rule = block.extensions.swagCmsExtensionsBlockRule;

                    return rule && (rule.inverted || !!rule.visibilityRuleId);
                });
            },
        },
    });
}
