import template from './swag-cms-extensions-form-editor-settings-field-type-number.html.twig';
import './swag-cms-extensions-form-editor-settings-field-type-number.scss';

const { Component } = Shopware;

Component.extend('swag-cms-extensions-form-editor-settings-field-type-number',
    'swag-cms-extensions-form-editor-settings-field-type-base',
    {
        name: 'swag-cms-extensions-form-editor-settings-field-type-number',

        template,

        computed: {
            min: {
                set(newMin) {
                    Shopware.State.commit('swCmsDetailCurrentCustomForm/setFieldConfigProperty', {
                        fieldId: this.field.id,
                        property: 'min',
                        value: newMin,
                    });
                },

                get() {
                    return this.field?.translated?.config?.min ?? this.field?.config?.min;
                },
            },

            max: {
                set(newMax) {
                    Shopware.State.commit('swCmsDetailCurrentCustomForm/setFieldConfigProperty', {
                        fieldId: this.field.id,
                        property: 'max',
                        value: newMax,
                    });
                },

                get() {
                    return this.field?.translated?.config?.max ?? this.field?.config?.max;
                },
            },

            step: {
                set(newStep) {
                    Shopware.State.commit('swCmsDetailCurrentCustomForm/setFieldConfigProperty', {
                        fieldId: this.field.id,
                        property: 'step',
                        value: newStep,
                    });
                },

                get() {
                    return this.field?.translated?.config?.step ?? this.field?.config?.step ?? 1;
                },
            },
        },
    });
