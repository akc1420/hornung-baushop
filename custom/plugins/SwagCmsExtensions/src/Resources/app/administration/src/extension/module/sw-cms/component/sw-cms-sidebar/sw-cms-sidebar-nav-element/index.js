import template from './sw-cms-sidebar-nav-element.html.twig';
import './sw-cms-sidebar-nav-element.scss';

const { Component, Feature } = Shopware;

if (Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_8') || Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_63')) {
    Component.override('sw-cms-sidebar-nav-element', {
        template,

        inject: [
            'feature',
        ],

        computed: {
            hasBlockRule() {
                const rule = this.block.extensions.swagCmsExtensionsBlockRule;

                return Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_8') && (rule?.inverted || !!rule?.visibilityRuleId);
            },

            ruleTooltip() {
                return {
                    message: this.$tc('swag-cms-extensions.sw-cms.sidebar.navElement.blockRuleTooltip'),
                };
            },

            blockRuleNavigatorClasses() {
                return {
                    'has--rule': Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_8') && this.hasBlockRule,
                };
            },
        },
    });
}
