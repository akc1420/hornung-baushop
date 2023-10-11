import template from './sw-cms-page-form.html.twig';
import './sw-cms-page-form.scss';

const { Component } = Shopware;

if (Shopware.Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_2')) {
    Component.override('sw-cms-page-form', {
        template,

        methods: {
            getScrollNavigationBySection(section) {
                return section.extensions.swagCmsExtensionsScrollNavigation;
            },

            getScrollNavigationLabel(section) {
                const prefix = this.$tc('swag-cms-extensions.sw-cms.section.actions.pageForm.labelPrefix');
                const scrollNavigation = this.getScrollNavigationBySection(section);

                const displayName = scrollNavigation && scrollNavigation.displayName !== null ?
                    scrollNavigation.displayName :
                    this.$tc('swag-cms-extensions.sw-cms.section.actions.pageForm.emptyAnchorName');

                return `${prefix} - ${displayName}`;
            },
        },
    });
}
