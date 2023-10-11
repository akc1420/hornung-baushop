import template from './sw-cms-sidebar.html.twig';
import './sw-cms-sidebar.scss';

const { Component, State } = Shopware;

Component.override('sw-cms-sidebar', {
    template,

    data() {
        return {
            sectionHeaderWrapperClass: 'sw-cms-sidebar__navigator-section-header-wrapper',
        };
    },

    computed: {
        sectionHeaderWrapperClasses() {
            return [this.sectionHeaderWrapperClass];
        },

        scrollNavigationFeatureActive() {
            return Shopware.Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_2');
        },
    },

    watch: {
        'cmsPageState.currentPage'() {
            State.commit('cmsPageState/removeSelectedSection');
        },
    },

    methods: {
        getScrollNavigationBySection(section) {
            return section.extensions.swagCmsExtensionsScrollNavigation;
        },

        scrollNavigationIsActive(section) {
            return this.scrollNavigationFeatureActive &&
                this.getScrollNavigationBySection(section) &&
                this.getScrollNavigationBySection(section).active;
        },

        navigatorSectionHeaderWrapperClass(section) {
            const scrollNavigation = this.getScrollNavigationBySection(section);

            return scrollNavigation && scrollNavigation.active ? [this.sectionHeaderWrapperClasses] : '';
        },

        scrollNavigationAnchorTooltip(section) {
            const scrollNavigation = this.getScrollNavigationBySection(section);
            const message = scrollNavigation && scrollNavigation.displayName !== null ?
                scrollNavigation.displayName :
                this.$tc('swag-cms-extensions.sw-cms.sidebar.emptyAnchorName');

            return {
                message,
            };
        },
    },
});
