const { Mixin } = Shopware;

Mixin.register('swag-cms-extensions-form-group-field-error', {
    methods: {
        validateDuplicateTechnicalName(form, field) {
            // Try to find field with identical technicalName
            const hasIdenticalTechnicalNameField = form.groups.some((group) => {
                return group.fields.some((f) => {
                    // If the field we are comparing to is the current field or the names do not match return
                    return (f.id !== field.id && f.technicalName === field.technicalName);
                });
            });

            if (!hasIdenticalTechnicalNameField) {
                return;
            }

            Shopware.State.commit('error/addApiError', {
                expression: `swag_cms_extensions_form_group_field.${field.id}.technicalName`,
                error: {
                    id: Shopware.Utils.createId(),
                    code: 'SWAG_CUSTOM_FORM_DUPLICATE_FIELD_TECHNICAL_NAME',
                    parameters: '__vue_devtool_undefined__',
                    status: '400',
                    detail: `The technical name (${field.technicalName}) is not unique in this form.`,
                },
            });
        },
    },
});
