import template from './sw-cms-page-form.html.twig';
import './sw-cms-page-form.scss';

const { Component } = Shopware;

if (Shopware.Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_8')) {
    Component.override('sw-cms-page-form', {
        template,

        methods: {
            sectionHasRulesOnAllBlocks(section) {
                return section.blocks.every((block) => {
                    const rule = block.extensions.swagCmsExtensionsBlockRule;

                    return rule && (rule.inverted || !!rule.visibilityRuleId);
                });
            },
        },
    });
}
