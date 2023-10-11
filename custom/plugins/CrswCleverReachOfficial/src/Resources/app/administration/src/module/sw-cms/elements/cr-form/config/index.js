import template from './sw-cms-el-config-cr-form.html.twig';
import './sw-cms-el-config-cr-form.scss';

const { Component, Mixin } = Shopware;
const Criteria = Shopware.Data.Criteria;

Component.register('sw-cms-el-config-cr-form', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    inject: ['repositoryFactory'],

    computed: {
        queueRepository() {
            return this.repositoryFactory.create('cleverreach_entity');
        },

        queueSelectContext() {
            const context = Object.assign({}, Shopware.Context.api);
            context.inheritance = true;

            return context;
        },

        queueCriteria() {
            let criteria = new Criteria();

            criteria.addFilter(Criteria.equals('type', 'Form'));

            return criteria;
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('cr-form');
        },

        onQueueChange(queueId) {
            if (!queueId) {
                this.element.config.queue.value = null;
                this.$set(this.element.data, 'queueId', null);
                this.$set(this.element.data, 'queue', null);
            } else {
                const criteria = new Criteria();

                this.queueRepository.get(queueId, this.queueSelectContext, criteria).then((queue) => {
                    this.element.config.queue.value = queue.shopware_id;
                    this.$set(this.element.data, 'queueId', queueId);
                    this.$set(this.element.data, 'queue', JSON.parse(queue.data));
                });
            }

            this.$emit('element-update', this.element);
        }
    }
});
