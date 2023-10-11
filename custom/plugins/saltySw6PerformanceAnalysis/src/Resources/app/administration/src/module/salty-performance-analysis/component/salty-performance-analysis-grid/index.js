import template from './salty-performance-analysis-grid.html.twig';
import { Component, Mixin } from 'src/core/shopware';

Component.register('salty-performance-analysis-grid', {
    template,

    inject: [
        'SaltyPerformanceAnalysisService',
    ],

    mixins: [
        Mixin.getByName('sw-inline-snippet')
    ],

    props: {
        values: {
            type: Array,
            required: true
        }
    },

    created() {
        this.createComponent();
    },

    computed: {
        gridColumns() {
            return this.getColumns();
        },

        isLoading() {
            return this.values.length === 0;
        }
    },

    methods: {
        createComponent() {
            this.getShopwareConfigurationInformation();
        },

        getColumns() {
           return [
               {
                   property: 'status',
                   label: 'saltyPerformanceAnalysis.columns.status',
                   rawData: true,
                   align: 'center',
                   width: '80px',
               },
               {
                   property: 'name',
                   label: 'saltyPerformanceAnalysis.columns.name',
                   rawData: true,
                   primary: true,
               },
               {
                   property: 'suggestedValue',
                   label: 'saltyPerformanceAnalysis.columns.recommended',
                   rawData: true,
                   align: 'center',
                   width: '135px',
               },
               {
                   property: 'value',
                   label: 'saltyPerformanceAnalysis.columns.value',
                   rawData: true,
                   align: 'center',
                   width: '75px',
               },
               {
                   property: 'information',
                   label: 'saltyPerformanceAnalysis.columns.information',
                   rawData: true,
                   align: 'center',
                   width: '120px',
               }
           ]
        },

        getShopwareConfigurationInformation() {
            this.SaltyPerformanceAnalysisService.getServerConfigurationInformation().then(response => {
                this.serverConfigurationInformation = response;
            });

            this.SaltyPerformanceAnalysisService.getShopwareConfigurationInformation().then(response => {
                this.shopwareConfigurationInformation = response;
            });
        },

        getSnippetName(name, category) {
            const prefix = 'saltyPerformanceAnalysis';
            let value = prefix + '.' + category + '.' + name;
            return value;
        }
    },
});
