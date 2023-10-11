import template from './swag-cms-extensions-form-editor-settings-field-type-footer.html.twig';

const { Component } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

Component.register('swag-cms-extensions-form-editor-settings-field-type-footer', {
    name: 'swag-cms-extensions-form-editor-settings-field-type-footer',

    template,

    computed: {
        ...mapState('swCmsDetailCurrentCustomForm', {
            field: state => state.activeItem,
        }),

        required: {
            set(newRequired) {
                Shopware.State.commit(
                    'swCmsDetailCurrentCustomForm/setFieldProperty',
                    {
                        fieldId: this.field.id,
                        property: 'required',
                        value: newRequired,
                    },
                );
            },

            get() {
                return this.field.required;
            },
        },

        errorMessage: {
            set(newErrorMessage) {
                Shopware.State.commit(
                    'swCmsDetailCurrentCustomForm/setFieldProperty',
                    {
                        fieldId: this.field.id,
                        property: 'errorMessage',
                        value: newErrorMessage,
                    },
                );
            },

            get() {
                return this.field.errorMessage;
            },
        },
    },
});
