import template from './swag-cms-extensions-section-scroll-behavior-config.html.twig';
import './swag-cms-extensions-section-scroll-behavior-config.scss';

const { Component, Application: { view: { setReactive } } } = Shopware;
const { mapPropertyErrors } = Component.getComponentHelper();

Component.register('swag-cms-extensions-section-scroll-behavior-config', {
    template,

    inject: [
        'repositoryFactory',
        'acl',
    ],

    model: {
        prop: 'section',
        event: 'section-change',
    },

    props: {
        section: {
            type: Object,
            required: true,
        },
    },

    computed: {
        scrollNavigationRepository() {
            return this.repositoryFactory.create('swag_cms_extensions_scroll_navigation');
        },

        scrollNavigationExtensionDefined() {
            return this.section.extensions.swagCmsExtensionsScrollNavigation !== undefined;
        },

        swagCmsExtensionsScrollNavigation() {
            if (this.scrollNavigationExtensionDefined) {
                return this.section.extensions.swagCmsExtensionsScrollNavigation;
            }
            return this.scrollNavigationRepository.create(Shopware.Context.api);
        },

        ...mapPropertyErrors('swagCmsExtensionsScrollNavigation', [
            'displayName',
        ]),
    },

    methods: {
        scrollNavigationActiveChanged() {
            /**
             * This will be executed, when the user activates the scroll navigation for the first
             * time and is necessary to prepare the extension to be saved later on.
             */
            setReactive(
                this.section.extensions,
                'swagCmsExtensionsScrollNavigation',
                this.swagCmsExtensionsScrollNavigation,
            );
            this.$emit('section-change', this.section);
        },
    },
});
