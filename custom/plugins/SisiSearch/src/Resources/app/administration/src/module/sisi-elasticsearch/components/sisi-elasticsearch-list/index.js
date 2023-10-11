import template from './sisi-elasticsearch-list.html.twig';


const {Component} = Shopware;

Component.register('sisi-elasticsearch-list', {
    template,
    name: 'SisiElasticsearch',
    inject: ['SisiApiCredentialsService', 'SisiElasticSearchIndexService'],
    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    data() {
        return {
            cluster: [],
            indices: [],
            salesChannelId: null
        }
    },

    computed: {
        columns() {
            return [
                {
                    label: 'sisi-elasticsearch.index.docs',
                    primary: true,
                    property: 'docs',
                    rawData: true,
                },
                {
                    label: 'sisi-elasticsearch.index.entity',
                    primary: true,
                    property: 'entity',
                    rawData: true,
                },
                {
                    label: 'sisi-elasticsearch.index.id',
                    primary: true,
                    property: 'id',
                    rawData: true
                },
                {
                    label: 'sisi-elasticsearch.index.isFinish',
                    primary: true,
                    property: 'isFinish',
                    rawData: true
                },
                {
                    label: 'sisi-elasticsearch.index.language',
                    primary: true,
                    property: 'language',
                    rawData: true
                },
                {
                    label: 'sisi-elasticsearch.index.index',
                    primary: true,
                    property: 'index',
                    rawData: true,
                },
                {
                    label: 'sisi-elasticsearch.index.shop',
                    primary: true,
                    property: 'shop',
                    rawData: true,
                },
                {
                    label: 'sisi-elasticsearch.index.size',
                    primary: true,
                    property: 'indexSize',
                    rawData: true,
                },
                {
                    label: 'sisi-elasticsearch.index.time',
                    primary: true,
                    property: 'time',
                    rawData: true,
                },
                {
                    label: 'sisi-elasticsearch.index.token',
                    primary: true,
                    property: 'token',
                    rawData: true,
                },
            ];
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.updateDisplay();
        },

        async deleteIndex(item) {
            await this.SisiElasticSearchIndexService.deleteIndex(item.index);
            this.updateDisplay();
        },

        onSalesChannelChanged(value) {
            this.salesChannelId = value;
            this.updateDisplay();
        },

        async updateDisplay() {
            this.isLoading = true;
            this.indices = await this.SisiElasticSearchIndexService.getEsIndexes(this.salesChannelId);
            this.cluster = await this.SisiElasticSearchIndexService.getStatus(this.salesChannelId);
            this.isLoading = false;
        }
    }
});
