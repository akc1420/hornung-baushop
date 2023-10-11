import template from './sw-cms-section-actions.html.twig';
import './sw-cms-section-actions.scss';

const { Component } = Shopware;

if (Shopware.Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_2')) {
    Component.override('sw-cms-section-actions', {
        template,

        computed: {
            scrollNavigation() {
                return this.section.extensions.swagCmsExtensionsScrollNavigation;
            },

            scrollNavigationPointTooltip() {
                const tooltipPrefix = this.$tc('swag-cms-extensions.sw-cms.section.actions.viewports.tooltipPrefix');
                const displayName = this.scrollNavigation && this.scrollNavigation.displayName ?
                    this.scrollNavigation.displayName :
                    this.$tc('swag-cms-extensions.sw-cms.section.actions.viewports.emptyAnchorName');

                return {
                    message: `${tooltipPrefix} ${displayName}`,
                    position: 'right',
                };
            },
        },
    });
}
