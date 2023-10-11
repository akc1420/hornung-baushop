import template from './sw-order-detail.html.twig';

const { Component, Utils, Mixin } = Shopware;
const { EntityCollection, Criteria } = Shopware.Data;
const { get, format, array } = Utils;

Component.override('sw-order-detail', {
    template,

    mixins: [
        Mixin.getByName('notification')
    ],

    inject: [
        'repositoryFactory',
        'orderService',
        'stateStyleDataProviderService',
        'acl',
        'feature',
    ],

    data() {
        return {
            items: null,
            openItems: []
        }
    },

    computed: {
        kzphSimplenoteRepository() {
            return this.repositoryFactory.create('kzph_simplenote');
        },
    },

    created() {
        this.createdSimplenoteComponent();
    },

    methods: {
        createdSimplenoteComponent() {
            const criteria = new Criteria();
            criteria.addFilter(
                Criteria.equals('entityId', this.$route.params.id)
            );

            this.kzphSimplenoteRepository
                .search(criteria, Shopware.Context.api)
                .then(result => {
                    this.items = result;

                    this.items.forEach((value, index) => {
                        if (value.done == 0)
                            this.openItems.push(value.id);
                    });

                    document.querySelector(".kzphSimplenoteBadge").textContent = this.openItems.length;
                    document.querySelector(".kzphSimplenoteBadge").classList.remove('hasSimplenotes');

                    if (this.openItems.length > 0)
                        document.querySelector(".kzphSimplenoteBadge").classList.add('hasSimplenotes');
                });

            //try to show entity notes
            this.orderRepository.get(this.orderId, Shopware.Context.api, this.orderCriteria).then((response) => {
                this._showEntityNoteMessage(response.orderCustomer.customerId);
                
                response.lineItems.forEach((lineItem) => {
                    this._showEntityNoteMessage(lineItem.productId);
                });
            }).catch(() => {
            });
        },
        _showEntityNoteMessage(entityId) {
            const criteria = new Criteria();
            criteria.addFilter(
                Criteria.equals('entityId', entityId)
            );
            criteria.addFilter(
                Criteria.equals('showMessage', 1)
            );

            this.kzphSimplenoteRepository
                .search(criteria, Shopware.Context.api)
                .then(result => {
                    result.forEach((entity) => {
                        this.createNotificationWarning({ title: this.$tc('kzph-simplenote-note.general.title'), message: entity.note })
                    });
                });
        }
    }
});