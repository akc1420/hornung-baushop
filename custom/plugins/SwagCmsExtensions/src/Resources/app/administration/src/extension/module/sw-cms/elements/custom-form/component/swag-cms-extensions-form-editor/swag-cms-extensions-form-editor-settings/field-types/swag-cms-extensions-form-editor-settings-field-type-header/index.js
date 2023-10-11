import template from './swag-cms-extensions-form-editor-settings-field-type-header.html.twig';

const { Component } = Shopware;
const { mapState, mapPropertyErrors } = Shopware.Component.getComponentHelper();

Component.register('swag-cms-extensions-form-editor-settings-field-type-header', {
    name: 'swag-cms-extensions-form-editor-settings-field-type-header',

    template,

    mixins: [
        'swag-cms-extensions-form-group-field-error',
    ],

    computed: {
        ...mapState('swCmsDetailCurrentCustomForm', {
            field: state => state.activeItem,
            form: state => state.form,
        }),

        ...mapPropertyErrors('field', [
            'technicalName',
            'label',
            'type',
            'width',
        ]),

        technicalName: {
            set(newTechnicalName) {
                Shopware.State.commit(
                    'swCmsDetailCurrentCustomForm/setFieldProperty',
                    {
                        fieldId: this.field.id,
                        property: 'technicalName',
                        value: newTechnicalName,
                    },
                );
            },

            get() {
                return this.field.technicalName;
            },
        },

        label: {
            set(newLabel) {
                Shopware.State.commit(
                    'swCmsDetailCurrentCustomForm/setFieldProperty',
                    {
                        fieldId: this.field.id,
                        property: 'label',
                        value: newLabel,
                    },
                );
            },

            get() {
                return this.field.label;
            },
        },

        type: {
            set(newType) {
                Shopware.State.commit(
                    'swCmsDetailCurrentCustomForm/setFieldType',
                    {
                        fieldId: this.field.id,
                        type: newType,
                    },
                );
            },

            get() {
                return this.field.type;
            },
        },

        width: {
            set(newWidth) {
                Shopware.State.commit(
                    'swCmsDetailCurrentCustomForm/setFieldProperty',
                    {
                        fieldId: this.field.id,
                        property: 'width',
                        value: newWidth,
                    },
                );
            },

            get() {
                return this.field.width;
            },
        },

        types() {
            return [
                {
                    value: 'text',
                    label: this.$tc('swag-cms-extensions.sw-cms.components.form-editor.settings-field.types.text'),
                },
                {
                    value: 'email',
                    label: this.$tc('swag-cms-extensions.sw-cms.components.form-editor.settings-field.types.email'),
                },
                {
                    value: 'number',
                    label: this.$tc('swag-cms-extensions.sw-cms.components.form-editor.settings-field.types.number'),
                },
                {
                    value: 'checkbox',
                    label: this.$tc('swag-cms-extensions.sw-cms.components.form-editor.settings-field.types.checkbox'),
                },
                {
                    value: 'select',
                    label: this.$tc('swag-cms-extensions.sw-cms.components.form-editor.settings-field.types.select'),
                },
                {
                    value: 'textarea',
                    label: this.$tc('swag-cms-extensions.sw-cms.components.form-editor.settings-field.types.textarea'),
                },
            ];
        },

        widths() {
            return [
                {
                    value: 1,
                    label: '8%',
                },
                {
                    value: 2,
                    label: '17%',
                },
                {
                    value: 3,
                    label: '25%',
                },
                {
                    value: 4,
                    label: '33%',
                },
                {
                    value: 5,
                    label: '42%',
                },
                {
                    value: 6,
                    label: '50%',
                },
                {
                    value: 7,
                    label: '58%',
                },
                {
                    value: 8,
                    label: '67%',
                },
                {
                    value: 9,
                    label: '75%',
                },
                {
                    value: 10,
                    label: '83%',
                },
                {
                    value: 11,
                    label: '92%',
                },
                {
                    value: 12,
                    label: '100%',
                },
            ];
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            // if no field is persisted yet, one could have changed the other field and we can revalidate
            // otherwise, the DAL is not able to resolve that first the old field has to be renamed first
            // we therefore need to first name this field differently and the error has to stay
            if (this.fieldTechnicalNameError && this.form._isNew) {
                Shopware.State.commit('error/removeApiError', {
                    expression: `swag_cms_extensions_form_group_field.${this.field.id}.technicalName`,
                });
                this.validateDuplicateTechnicalName(this.form, this.field);
            }
        },

        onTechnicalNameChange(newValue) {
            // If technicalName includes spaces remove all of them
            if (newValue.match(/\s/)) {
                this.technicalName = newValue.replaceAll(/\s/g, '');
            }

            this.$nextTick(() => {
                this.validateDuplicateTechnicalName(this.form, this.field);
            });
        },
    },
});
