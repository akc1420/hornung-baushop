import template from './swag-cms-extensions-page-scroll-behavior-config.html.twig';
import './swag-cms-extensions-page-scroll-behavior-config.scss';

const { Component, Application: { view: { setReactive } } } = Shopware;

Component.register('swag-cms-extensions-page-scroll-behavior-config', {
    template,

    inject: [
        'repositoryFactory',
        'acl',
    ],

    model: {
        prop: 'page',
        event: 'page-change',
    },

    props: {
        page: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            defaults: {
                active: false,
                duration: 1000,
                easing: 'inOut',
                easingDegree: 3,
                bouncy: false,
            },
            easings: ['linear', 'in', 'out', 'inOut'],
        };
    },

    computed: {
        scrollNavigationPageSettingsRepository() {
            return this.repositoryFactory.create('swag_cms_extensions_scroll_navigation_page_settings');
        },

        scrollNavigationPageSettingsDefined() {
            return this.page.extensions.swagCmsExtensionsScrollNavigationPageSettings !== undefined;
        },

        scrollNavigationPageSettings() {
            if (this.scrollNavigationPageSettingsDefined) {
                return this.page.extensions.swagCmsExtensionsScrollNavigationPageSettings;
            }
            return this.scrollNavigationPageSettingsRepository.create(Shopware.Context.api);
        },

        pageHasNavigationPoints() {
            return this.page.sections.some((section) => {
                const scrollNavigation = section.extensions.swagCmsExtensionsScrollNavigation;

                return scrollNavigation && scrollNavigation.active;
            });
        },

        smoothScrollingActive() {
            return this.pageHasNavigationPoints && this.scrollNavigationPageSettings.active;
        },

        easingTypes() {
            return this.easings.map((type) => {
                return {
                    value: type,
                    label: this.$tc(`swag-cms-extensions.sw-cms.detail.scrollNavigation.pageSettings.easing.${type}`),
                };
            });
        },

        easingDegreeDisabled() {
            return !this.smoothScrollingActive ||
                this.scrollNavigationPageSettings.easing === 'linear' ||
                this.scrollNavigationPageSettings.bouncy === true;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.setDefaults();
        },

        setDefaults() {
            Object.entries(this.defaults).forEach(([property, defaultValue]) => {
                if (this.scrollNavigationPageSettings[property] === undefined) {
                    this.scrollNavigationPageSettings[property] = defaultValue;
                    this.pageSettingsActiveChanged();
                }
            });
        },

        pageSettingsActiveChanged() {
            /**
             * This will be executed, when the user uses the page settings for the first
             * time and is necessary to prepare the extension to be saved later on.
             */
            setReactive(
                this.page.extensions,
                'swagCmsExtensionsScrollNavigationPageSettings',
                this.scrollNavigationPageSettings,
            );
            this.$emit('page-change', this.page);
        },
    },

});
