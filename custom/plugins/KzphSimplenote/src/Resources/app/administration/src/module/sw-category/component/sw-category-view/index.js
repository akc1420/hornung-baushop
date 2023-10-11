import template from './sw-category-view.html.twig';

const {Component, Context} = Shopware;
const {Criteria} = Shopware.Data;

Component.override('sw-category-view', {
    template,

    inject: [
        'repositoryFactory'
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

                if(this.openItems.length > 0)
                    document.querySelector(".kzphSimplenoteBadge").classList.add('hasSimplenotes');
            });
        },
    }
});