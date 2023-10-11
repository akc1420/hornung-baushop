import template from './sisi-scheduled-list.html.twig';

const {Component} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('sisi-scheduled-list', {
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
            return [
                {
                property: 'title',
                dataIndex: 'title',
                label: this.$t('sisi-scheduled.list.title'),
                inlineEdit: 'string',
                allowResize: true,
                primary: true
                }, {
                property: 'shop',
                label: this.$t('sisi-scheduled.list.shop'),
                inlineEdit: 'string',
                allowResize: true,
                 },
                 {
               property: 'language',
               label: this.$t('sisi-scheduled.list.language'),
               inlineEdit: 'string',
               allowResize: true,
                 },
                {
               property: 'aktive',
               label: this.$t('sisi-scheduled.list.aktive'),
               inlineEdit: 'string',
               allowResize: true,
                }
            ]
        }
    },
    created() {
        var criteria = new Criteria();
        criteria.addSorting(Criteria.sort('title', 'ASC'));
        this.repository = this.repositoryFactory.create('sisi_search_es_scheduledtask');
        this.repository
            .search(criteria, Shopware.Context.api)
            .then((result) => {
                this.fields = result;
            });
    }
});
