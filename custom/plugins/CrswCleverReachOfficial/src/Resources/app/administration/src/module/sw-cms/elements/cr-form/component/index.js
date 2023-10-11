import template from './sw-cms-el-cr-form.html.twig';
import './sw-cms-el-cr-form.scss';

const {Component, Mixin} = Shopware;

Component.register('sw-cms-el-cr-form', {
    template,

    inject: [
        'cleverreachService'
    ],

    mixins: [
        Mixin.getByName('cms-element'),
        Mixin.getByName('placeholder')
    ],

    computed: {
        queue() {
            let htmlCode = document.createElement('div');

            if (this.element.data.hasOwnProperty('queue') && this.element.data.queue !== null) {
                if (this.element.data.queue.content) {
                    htmlCode.innerHTML = this.element.data.queue.content;
                }

                if (this.element.data.queue.data) {
                    htmlCode.innerHTML = JSON.parse(this.element.data.queue.data).content;
                }
            } else {
                this.$set(this.element.data, 'queue', {htmlContent: 'Your CR form'.trim()});
            }

            if (!htmlCode.innerHTML) {
                this.cleverreachService.getDefaultForm()
                    .then((form) => {
                        this.element.config.queue.value = form.defaultForm.shopware_id;
                        this.$set(this.element.data, 'queueId', form.defaultForm.shopware_id);
                        this.$set(this.element.data, 'queue', JSON.parse(form.defaultForm.data));

                        htmlCode.innerHTML = this.element.data.queue.content;
                        this.element.data.queue.htmlContent = htmlCode.childNodes[0].nodeValue;

                        return this.element.data.queue;
                    });

                return this.element.data.queue;
            } else {
                this.element.data.queue.htmlContent = htmlCode.childNodes[0].nodeValue;

                return this.element.data.queue;
            }
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('cr-form');
            this.initElementData('cr-form');
        }
    }
});
