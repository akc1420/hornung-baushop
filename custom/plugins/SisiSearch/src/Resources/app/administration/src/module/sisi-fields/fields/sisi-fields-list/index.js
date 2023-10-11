import template from './sisi-fields-list.html.twig';

const {Component} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('sisi-fields-list', {
    template,

    inject: [
        'repositoryFactory'
    ],

    data() {
        return {
            fields: null,
            isLoading: true
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {

        teaserColumns() {
            return [{
                property: 'name',
                dataIndex: 'name',
                label: this.$t('ssisi-fields.list.name'),
                inlineEdit: 'string',
                allowResize: true,
                primary: true
            }, {
                property: 'prefix',
                dataIndex: 'name',
                label: this.$t('ssisi-fields.list.prefix'),
                inlineEdit: 'string',
                allowResize: true,
                primary: true
            }, {
                property: 'tablename',
                label: this.$t('ssisi-fields.list.tablename'),
                inlineEdit: 'string',
                allowResize: true,
            },
                {
                    property: 'shop',
                    label: this.$t('ssisi-fields.list.shop'),
                    inlineEdit: 'string',
                    allowResize: true,
                },
                {
                    property: 'shoplanguage',
                    label: this.$t('ssisi-fields.list.shoplanguage'),
                    inlineEdit: 'string',
                    allowResize: true,
                }

            ]

        }
    },

    created() {
        var criteria = new Criteria();
        criteria.addSorting(Criteria.sort('name', 'ASC'));
        this.repository = this.repositoryFactory.create('s_plugin_sisi_search_es_fields');
        this.repository
            .search(criteria, Shopware.Context.api)
            .then((result) => {
                this.fields = result;
            });
    }
});
