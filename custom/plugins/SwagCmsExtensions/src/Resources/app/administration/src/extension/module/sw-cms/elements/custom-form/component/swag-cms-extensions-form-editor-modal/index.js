import template from './swag-cms-extensions-form-editor-modal.html.twig';
import './swag-cms-extensions-form-editor-modal.scss';

const { Component } = Shopware;
const { Criteria, ChangesetGenerator, ErrorResolver } = Shopware.Data;
const { mapState, mapPropertyErrors } = Shopware.Component.getComponentHelper();

Component.register('swag-cms-extensions-form-editor-modal', {
    name: 'swag-cms-extensions-form-editor-modal',

    template,

    inject: [
        'SwagCmsExtensionsFormValidationService',
    ],

    mixins: [
        'cms-state',
    ],

    data() {
        return {
            displayModal: true,
            showCreateTemplateModal: false,
            initiallyValidated: false,
        };
    },

    computed: {
        ...mapState('swCmsDetailCurrentCustomForm', [
            'form',
        ]),

        ...mapPropertyErrors('form', [
            'technicalName',
            'title',
            'confirmationText',
            'receivers',
            'mailTemplateId',
        ]),

        technicalName: {
            set(newTechnicalName) {
                Shopware.State.commit(
                    'swCmsDetailCurrentCustomForm/setFormProperty',
                    {
                        property: 'technicalName',
                        value: newTechnicalName,
                    },
                );
            },

            get() {
                if (this.form === null) {
                    return '';
                }

                return this.form.technicalName;
            },
        },

        mailTemplateId: {
            set(newMailTemplateId) {
                Shopware.State.commit(
                    'swCmsDetailCurrentCustomForm/setFormProperty',
                    {
                        property: 'mailTemplateId',
                        value: newMailTemplateId,
                    },
                );
            },

            get() {
                if (this.form === null) {
                    return null;
                }

                return this.form.mailTemplateId;
            },
        },

        title: {
            set(newTitle) {
                Shopware.State.commit(
                    'swCmsDetailCurrentCustomForm/setFormProperty',
                    {
                        property: 'title',
                        value: newTitle,
                    },
                );
            },

            get() {
                if (this.form === null) {
                    return '';
                }

                return this.form.title;
            },
        },

        successMessage: {
            set(newSuccessMessage) {
                Shopware.State.commit(
                    'swCmsDetailCurrentCustomForm/setFormProperty',
                    {
                        property: 'successMessage',
                        value: newSuccessMessage,
                    },
                );
            },

            get() {
                if (this.form === null) {
                    return '';
                }

                return this.form.successMessage;
            },
        },

        receivers: {
            set(newReceivers) {
                Shopware.State.commit(
                    'swCmsDetailCurrentCustomForm/setFormProperty',
                    {
                        property: 'receivers',
                        value: newReceivers,
                    },
                );
            },

            get() {
                if (this.form === null) {
                    return [];
                }

                return this.form.receivers;
            },
        },

        mailTemplateCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('mailTemplateType');
            criteria.addFilter(
                Criteria.equals('mailTemplateType.technicalName', 'cms_extensions.form'),
            );

            return criteria;
        },

        fieldTabHasError() {
            const fieldProperties = [
                'width',
                'type',
                'technicalName',
                'label',
                'config',
            ];

            const fieldErrorExists = fieldProperties.reduce((acc, property) => {
                if (acc) {
                    return acc;
                }

                return Shopware.State.getters['error/existsErrorInProperty'](
                    'swag_cms_extensions_form_group_field',
                    property,
                );
            }, false);

            if (fieldErrorExists) {
                return true;
            }

            return Shopware.State.getters['error/existsErrorInProperty']('swag_cms_extensions_form_group', 'technicalName');
        },

        optionsTabHasError() {
            return !!(this.formTechnicalNameError ||
                this.formTitleError ||
                this.formConfirmationTextError ||
                this.formReceiversError ||
                this.formMailTemplateIdError
            );
        },
    },

    watch: {
        // Empty values get filtered by the DAL this watcher prevents Array function calls on null
        receivers() {
            if (Array.isArray(this.receivers)) {
                return;
            }

            this.receivers = [];
        },

        form: {
            handler() {
                if (this.initiallyValidated || !this.form) {
                    return;
                }

                if ((Shopware.Utils.object.hasOwnProperty(this.form, 'isFirstEdit') && this.form.isFirstEdit)) {
                    return;
                }

                this.validateForm().catch((error) => {
                    // The form is not valid. Add the api errors to the error state by hand.
                    this.handleErrorResponse(error);
                }).finally(() => {
                    this.initiallyValidated = true;
                });
            },
            immediate: true,
            deep: true,
        },
    },

    methods: {
        onModalClose(persist) {
            if (this.form.isFirstEdit) {
                delete this.form.isFirstEdit;
            }

            if (!persist) {
                // This dismounts the sw-modal it self
                this.displayModal = false;

                // The event fired is used to dismount this component
                this.$nextTick(() => {
                    this.$emit('modal-close', persist);
                });

                return;
            }

            this.validateForm().then(() => {
                // Form is valid for the DAL. Technical names of the fields are validated by the Admin.
                // Check if a error for the technicalName of the Field still exists.
                if (Shopware.State.getters['error/existsErrorInProperty'](
                    'swag_cms_extensions_form_group_field',
                    'technicalName',
                )) {
                    return;
                }

                // This dismounts the sw-modal it self
                this.displayModal = false;

                // The event fired is used to dismount this component
                this.$nextTick(() => {
                    this.$emit('modal-close', persist);
                });
            }).catch((error) => {
                // The form is not valid. Add the api errors to the error state by hand.
                this.handleErrorResponse(error);
            });
        },

        onCreateTemplate() {
            this.validateForm().then(() => {
                if (Shopware.State.getters['error/existsErrorInProperty'](
                    'swag_cms_extensions_form_group_field',
                    'technicalName',
                )) {
                    return;
                }

                this.showCreateTemplateModal = true;
            }).catch((error) => {
                this.handleErrorResponse(error);
            });
        },

        onCreateTemplateModalClose() {
            this.showCreateTemplateModal = false;
        },

        onChangeTechnicalName(newValue) {
            // If technicalName includes spaces remove all of them
            if (newValue.match(/\s/)) {
                this.technicalName = newValue.replaceAll(/\s/g, '');
            }
        },

        validateForm() {
            const generator = new ChangesetGenerator();
            const form = Shopware.State.get('swCmsDetailCurrentCustomForm').form;
            const changes = generator.generate(form).changes;

            if (!changes) {
                return Promise.resolve();
            }

            // For each form related entity, we add an empty translation if there are none to retrieve all errors
            if (!Shopware.Utils.object.hasOwnProperty(changes, 'translations')) {
                changes.translations = [
                    {
                        languageId: Shopware.Defaults.systemLanguageId,
                    },
                ];
            }

            if (Shopware.Utils.object.hasOwnProperty(changes, 'groups')) {
                changes.groups.forEach((group) => {
                    if (!Shopware.Utils.object.hasOwnProperty(group, 'translations')) {
                        group.translations = [
                            {
                                languageId: Shopware.Defaults.systemLanguageId,
                            },
                        ];
                    }

                    if (Shopware.Utils.object.hasOwnProperty(group, 'fields')) {
                        group.fields.forEach((field) => {
                            if (!Shopware.Utils.object.hasOwnProperty(field, 'translations')) {
                                field.translations = [
                                    {
                                        languageId: Shopware.Defaults.systemLanguageId,
                                    },
                                ];
                            }
                        });
                    }
                });
            }

            return this.SwagCmsExtensionsFormValidationService.validateForm(
                {
                    id: form.id,
                    ...changes,
                },
            ).then((response) => {
                // Try to find form with identical technicalName
                const hasIdenticalTechnicalNameForm = this.cmsPageState.currentPage.sections.some((section) => {
                    return section.blocks.some((block) => {
                        return block.slots.some((slot) => {
                            if (!Shopware.Utils.object.hasOwnProperty(slot, 'extensions') ||
                                !Shopware.Utils.object.hasOwnProperty(slot.extensions, 'swagCmsExtensionsForm')
                            ) {
                                return false;
                            }

                            // If the form we are comparing to is the current form or the names do not match return
                            return (slot.extensions.swagCmsExtensionsForm.id !== form.id
                                 && slot.extensions.swagCmsExtensionsForm.technicalName === form.technicalName);
                        });
                    });
                });

                if (!hasIdenticalTechnicalNameForm) {
                    return Promise.resolve(response);
                }

                Shopware.State.commit('error/addApiError', {
                    expression: `swag_cms_extensions_form.${form.id}.technicalName`,
                    error: {
                        id: Shopware.Utils.createId(),
                        code: 'SWAG_CUSTOM_FORM_DUPLICATE_FORM_TECHNICAL_NAME',
                        parameters: '__vue_devtool_undefined__',
                        status: '400',
                        detail: `The technical name (${form.technicalName}) is not unique in this page.`,
                    },
                });

                return Promise.reject();
            });
        },

        handleErrorResponse(error) {
            const resolver = new ErrorResolver();
            const generator = new ChangesetGenerator();
            const form = Shopware.State.get('swCmsDetailCurrentCustomForm').form;

            resolver.handleWriteErrors(
                error.response.data,
                [
                    {
                        entity: form,
                        changes: generator.generate(form).changes,
                    },
                ],
            );
        },
    },
});
