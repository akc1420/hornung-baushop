import template from './salty-performance-analysis-overview.html.twig';
import { Component, Mixin } from 'src/core/shopware';

Component.register('salty-performance-analysis-overview', {
    template,

    inject: [
        'SaltyPerformanceAnalysisService',
    ],

    mixins: [
        Mixin.getByName('sw-inline-snippet')
    ],

    data() {
        return {
            shopwareConfigurationInformation: [],
            serverConfigurationInformation: [],
        }
    },

    created() {
        this.createComponent();
    },

    methods: {
        createComponent() {
            this.getShopwareConfigurationInformation();
            this.getServerConfigurationInformation();
        },

        getServerConfigurationInformation() {
            this.SaltyPerformanceAnalysisService.getServerConfigurationInformation().then(response => {
                this.serverConfigurationInformation = response;
            });
        },

        getShopwareConfigurationInformation() {
            this.SaltyPerformanceAnalysisService.getShopwareConfigurationInformation().then(response => {
                this.shopwareConfigurationInformation = response;
            });
        },
    },
});
