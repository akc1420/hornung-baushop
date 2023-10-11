import template from './swag-cms-extensions-form-editor-settings-field-type-checkbox.html.twig';

const { Component } = Shopware;

Component.extend('swag-cms-extensions-form-editor-settings-field-type-checkbox',
    'swag-cms-extensions-form-editor-settings-field-type-base',
    {
        name: 'swag-cms-extensions-form-editor-settings-field-type-checkbox',

        template,

        computed: {
            defaultValue: {
                set(newDefault) {
                    Shopware.State.commit('swCmsDetailCurrentCustomForm/setFieldConfigProperty', {
                        fieldId: this.field.id,
                        property: 'default',
                        value: newDefault,
                    });
                },

                get() {
                    return this.field?.translated?.config?.default ?? this.field?.config?.default ?? false;
                },
            },

            values() {
                return [
                    {
                        value: true,
                        label: this.$tc(
                            'swag-cms-extensions.sw-cms.components.form-editor.settings-field.defaultValues.checked',
                        ),
                    },
                    {
                        value: false,
                        label: this.$tc(
                            'swag-cms-extensions.sw-cms.components.form-editor.settings-field.defaultValues.notChecked',
                        ),
                    },
                ];
            },
        },
    });
