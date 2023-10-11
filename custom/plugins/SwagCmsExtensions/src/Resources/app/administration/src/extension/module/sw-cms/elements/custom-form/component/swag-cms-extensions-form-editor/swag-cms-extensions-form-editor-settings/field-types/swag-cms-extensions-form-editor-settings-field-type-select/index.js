import template from './swag-cms-extensions-form-editor-settings-field-type-select.html.twig';

const { Component } = Shopware;
const { mapPropertyErrors } = Shopware.Component.getComponentHelper();

Component.extend('swag-cms-extensions-form-editor-settings-field-type-select',
    'swag-cms-extensions-form-editor-settings-field-type-base',
    {
        name: 'swag-cms-extensions-form-editor-settings-field-type-select',

        template,

        data() {
            return {
                mode: null,
            };
        },

        computed: {
            ...mapPropertyErrors('field', [
                'config.options',
            ]),

            options: {
                set(newOptions) {
                    Shopware.State.commit('swCmsDetailCurrentCustomForm/setFieldConfigProperty', {
                        fieldId: this.field.id,
                        property: 'options',
                        value: Array.from(new Set(newOptions)),
                    });
                },

                get() {
                    return this.field?.translated?.config?.options ?? this.field?.config?.options;
                },
            },

            entity: {
                set(newEntity) {
                    Shopware.State.commit('swCmsDetailCurrentCustomForm/setFieldConfigProperty', {
                        fieldId: this.field.id,
                        property: 'entity',
                        value: newEntity,
                    });
                },

                get() {
                    return this.field?.translated?.config?.entity ?? this.field?.config?.entity;
                },
            },

            modes() {
                return [
                    {
                        value: 'entity',
                        label: this.$tc(
                            'swag-cms-extensions.sw-cms.components.form-editor.settings-field.modeOptionEntity',
                        ),
                    },
                    {
                        value: 'custom',
                        label: this.$tc(
                            'swag-cms-extensions.sw-cms.components.form-editor.settings-field.modeOptionCustom',
                        ),
                    },
                ];
            },

            availableEntities() {
                return [
                    {
                        value: 'country',
                        label: this.$tc('global.entities.country'),
                    },
                    {
                        value: 'salutation',
                        label: this.$tc('global.entities.salutation'),
                    },
                ];
            },
        },

        watch: {
            options() {
                this.updateMode();
            },
        },

        created() {
            this.createdComponent();
        },

        methods: {
            createdComponent() {
                this.updateMode();
            },

            updateMode() {
                if (this.fieldConfigOptionsError || this.options?.length > 0) {
                    this.mode = 'custom';
                    return;
                }

                this.mode = 'entity';
            },

            onModeChange() {
                Shopware.State.commit('swCmsDetailCurrentCustomForm/changeFieldSelectConfig', {
                    fieldId: this.field.id,
                    mode: this.mode,
                });
            },

            onCustomOptionsChange() {
                if (!this.fieldConfigOptionsError) {
                    return;
                }

                Shopware.State.dispatch(
                    'error/removeApiError',
                    { expression: this.fieldConfigOptionsError.selfLink },
                );
            },
        },
    });
