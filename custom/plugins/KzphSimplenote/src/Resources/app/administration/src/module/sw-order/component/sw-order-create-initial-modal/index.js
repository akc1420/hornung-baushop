import template from './sw-order-create-initial-modal.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.override('sw-order-create-initial-modal', {
    template,

    mixins: [
        Mixin.getByName('notification')
    ],

    inject: [
        'repositoryFactory'
    ],

    computed: {
        kzphSimplenoteRepository() {
            return this.repositoryFactory.create('kzph_simplenote');
        }
    },

    methods: {

        onPreviewOrder() {
            this.$super('onPreviewOrder');
 
            if (!this.customer?.id) {
                return;
            }

            const criteria = new Criteria();
            criteria.addFilter(Criteria.multi('AND', [
                Criteria.equals('entityId', this.customer?.id),
                Criteria.equals('showMessage', 1),
            ]));
            criteria.addSorting(Criteria.sort('createdAt', 'desc'));

            this.kzphSimplenoteRepository
                .search(criteria, Shopware.Context.api)
                .then(result => {
                    result.forEach((entity) => {
                       this.createNotificationWarning({ title: this.$tc('kzph-simplenote-note.general.title'), message: entity.note })
                    });
                });
        },

    },
});