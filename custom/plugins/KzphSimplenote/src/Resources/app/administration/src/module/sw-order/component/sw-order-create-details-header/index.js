import template from './sw-order-create-details-header.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.override('sw-order-create-details-header', {
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
        onSelectExistingCustomer(customerId) {
            this.$super('onSelectExistingCustomer', customerId);

            if (!customerId) {
                return;
            }

            const criteria = new Criteria();
            criteria.addFilter(Criteria.multi('AND', [
                Criteria.equals('entityId', customerId),
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
