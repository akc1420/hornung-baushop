import template from './sw-order-product-select.html.twig';

const { Component, Service, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.override('sw-order-product-select', {
    template,

    mixins: [
        Mixin.getByName('notification')
    ],

    computed: {
        productRepository() {
            return Service('repositoryFactory').create('product');
        },
        kzphSimplenoteRepository() {
            return Service('repositoryFactory').create('kzph_simplenote');
        }
    },

    methods: {
        onItemChanged(newProductId) {
            this.$super('onItemChanged', newProductId);

            this.productRepository.get(newProductId, this.contextWithInheritance).then((newProduct) => {

                if (!newProduct.id) {
                    return;
                }
    
                const criteria = new Criteria();
                criteria.addFilter(Criteria.multi('AND', [
                    Criteria.equals('entityId', newProduct.id),
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
            });
        },
    },
});
