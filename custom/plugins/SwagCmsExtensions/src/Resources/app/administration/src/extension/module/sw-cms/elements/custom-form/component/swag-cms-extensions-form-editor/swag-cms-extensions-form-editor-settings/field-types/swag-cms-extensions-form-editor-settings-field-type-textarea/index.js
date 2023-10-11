import template from './swag-cms-extensions-form-editor-settings-field-type-textarea.html.twig';

const { Component } = Shopware;

Component.extend('swag-cms-extensions-form-editor-settings-field-type-textarea',
    'swag-cms-extensions-form-editor-settings-field-type-text',
    {
        name: 'swag-cms-extensions-form-editor-settings-field-type-textarea',

        template,

        computed: {
            rows: {
                set(newRows) {
                    Shopware.State.commit('swCmsDetailCurrentCustomForm/setFieldConfigProperty', {
                        fieldId: this.field.id,
                        property: 'rows',
                        value: newRows,
                    });
                },

                get() {
                    return this.field?.translated?.config?.rows ?? this.field?.config?.rows ?? 5;
                },
            },

            scalable: {
                set(newScalable) {
                    Shopware.State.commit('swCmsDetailCurrentCustomForm/setFieldConfigProperty', {
                        fieldId: this.field.id,
                        property: 'scalable',
                        value: newScalable,
                    });
                },

                get() {
                    return this.field?.translated?.config?.scalable ?? this.field?.config?.scalable ?? true;
                },
            },

            rowsOptions() {
                const rows = [];
                for (let i = 1; i < 100; i += 1) {
                    rows.push(
                        {
                            value: i,
                            label: this.$tc(
                                'swag-cms-extensions.sw-cms.components.form-editor.settings-field.rowsLabel',
                                i,
                            ),
                        },
                    );
                }

                return rows;
            },
        },
    });
