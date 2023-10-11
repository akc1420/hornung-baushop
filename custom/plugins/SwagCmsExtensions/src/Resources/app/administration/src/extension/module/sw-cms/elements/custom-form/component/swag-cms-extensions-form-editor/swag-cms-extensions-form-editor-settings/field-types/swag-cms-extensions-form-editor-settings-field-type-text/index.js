import template from './swag-cms-extensions-form-editor-settings-field-type-text.html.twig';

const { Component } = Shopware;

Component.extend('swag-cms-extensions-form-editor-settings-field-type-text',
    'swag-cms-extensions-form-editor-settings-field-type-base',
    {
        name: 'swag-cms-extensions-form-editor-settings-field-type-text',

        template,

        computed: {
            placeholder: {
                set(newPlaceholder) {
                    Shopware.State.commit('swCmsDetailCurrentCustomForm/setFieldProperty', {
                        fieldId: this.field.id,
                        property: 'placeholder',
                        value: newPlaceholder,
                    });
                },

                get() {
                    return this.field.placeholder;
                },
            },
        },
    });
