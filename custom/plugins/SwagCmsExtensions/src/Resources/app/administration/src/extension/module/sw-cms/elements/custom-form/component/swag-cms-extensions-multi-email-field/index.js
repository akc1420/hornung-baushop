import template from './swag-cms-extensions-multi-email-field.html.twig';

const { Component } = Shopware;

Component.extend('swag-cms-extensions-multi-email-field', 'sw-tagged-field', {
    template,

    props: {
        addOnKey: {
            type: Array,
            required: false,
            default: () => ['enter', ' ', ',', ';'],
        },
    },

    computed: {
        mailPattern() {
            return /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)+$/;
        },
    },

    methods: {
        performAddTag(event) {
            if (this.disabled || this.noTriggerKey(event)) {
                return;
            }

            if (typeof this.newTagName !== 'string' || this.newTagName === '') {
                return;
            }

            // is an email?
            if (this.newTagName.match(this.mailPattern) === null) {
                this.newTagName = '';
                return;
            }

            // no duplicates
            if (this.value.indexOf(this.newTagName) !== -1) {
                this.newTagName = '';
                return;
            }

            this.$emit('change', [...this.value, this.newTagName]);
            this.newTagName = '';
        },

        blurInput() {
            this.setFocus(false);

            // add on blur
            const fakeEvent = new Event('keydown');
            fakeEvent.key = this.addOnKey[0];
            this.performAddTag(fakeEvent);
        },
    },
});
