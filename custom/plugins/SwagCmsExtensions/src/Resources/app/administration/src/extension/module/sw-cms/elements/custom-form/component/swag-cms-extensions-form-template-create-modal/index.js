import template from './swag-cms-extensions-form-template-create-modal.html.twig';
import './swag-cms-extensions-form-template-create-modal.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-cms-extensions-form-template-create-modal', {
    name: 'swag-cms-extensions-form-template-create-modal',

    template,

    inject: [
        'repositoryFactory',
    ],

    data() {
        return {
            templateName: '',
            showOverwriteWarning: false,
            showSaveSuccess: false,
            showOverwriteSelect: false,
            showOverwriteConfirmation: false,
            wasOverwrite: false,
            successMessage: '',
            overwriteId: null,
        };
    },

    computed: {
        formRepository() {
            return this.repositoryFactory.create('swag_cms_extensions_form');
        },

        groupRepository() {
            return this.repositoryFactory.create('swag_cms_extensions_form_group');
        },

        fieldRepository() {
            return this.repositoryFactory.create('swag_cms_extensions_form_group_field');
        },

        primaryConfirmLabel() {
            if (this.showOverwriteWarning || this.showOverwriteSelect || this.showOverwriteConfirmation) {
                return this.$tc('swag-cms-extensions.sw-cms.elements.custom-form.createTemplateModal.overwriteButtonLabel');
            }

            if (this.showSaveSuccess) {
                return this.$tc('sw-wizard.closeButton');
            }

            return this.$tc('global.default.save');
        },

        templateCriteria() {
            const criteria = new Criteria();
            criteria.addFilter(
                Criteria.equals('isTemplate', true),
            );

            return criteria;
        },

        overwriteConfirmationText() {
            return this.$tc(
                'swag-cms-extensions.sw-cms.elements.custom-form.createTemplateModal.overwriteConfirmationText',
                0,
                {
                    templateName: this.templateName,
                },
            );
        },

        modalTitle() {
            if (this.showOverwriteWarning ||
                this.showOverwriteSelect ||
                this.showOverwriteConfirmation ||
                this.wasOverwrite
            ) {
                return this.$tc('swag-cms-extensions.sw-cms.elements.custom-form.createTemplateModal.titles.overwrite');
            }

            return this.$tc('swag-cms-extensions.sw-cms.elements.custom-form.createTemplateModal.titles.default');
        },
    },

    methods: {
        onTemplateNameChange() {
            if (!this.showOverwriteWarning) {
                return;
            }

            this.showOverwriteWarning = false;
        },

        onModalClose() {
            this.$emit('modal-close');
        },

        onPrimaryConfirm() {
            // Template got saved or overwritten, close modal
            if (this.showSaveSuccess) {
                this.onModalClose();
                return;
            }

            // If the overwrite warning is already displayed and the form id to overwrite is persisted, overwrite.
            if (this.showOverwriteConfirmation) {
                // Delete old template and save new one with same name. Uuid change does not matter
                this.formRepository.delete(this.overwriteId, Shopware.Context.api).then(() => {
                    this.formRepository.save(
                        Shopware.State.getters['swCmsDetailCurrentCustomForm/template'](
                            this.templateName,
                            this.formRepository,
                            this.groupRepository,
                            this.fieldRepository,
                        ),
                        Shopware.Context.api,
                    ).then(() => {
                        this.successMessage = this.$tc(
                            'swag-cms-extensions.sw-cms.elements.custom-form.createTemplateModal.overwriteMessage',
                        );
                        this.showOverwriteConfirmation = false;
                        this.overwriteId = null;
                        this.wasOverwrite = true;
                        this.showSaveSuccess = true;
                    });
                });

                return;
            }

            if ((this.showOverwriteWarning || this.showOverwriteSelect) && this.overwriteId) {
                this.showOverwriteWarning = false;
                this.showOverwriteSelect = false;
                this.showOverwriteConfirmation = true;
                return;
            }

            // Initial try to save a template, search for template with same technical Name
            const criteria = new Criteria(1, 1);
            criteria.addFilter(
                Criteria.multi('and', [
                    Criteria.equals('isTemplate', true),
                    Criteria.equals('technicalName', this.templateName),
                ]),
            );

            this.formRepository.search(criteria, Shopware.Context.api).then((result) => {
                // Template with same name found abort save and show overwrite warning
                if (result.total === 1) {
                    this.showOverwriteWarning = true;
                    this.overwriteId = result.first().id;
                    return;
                }

                // Save and show success message
                this.formRepository.save(
                    Shopware.State.getters['swCmsDetailCurrentCustomForm/template'](
                        this.templateName,
                        this.formRepository,
                        this.groupRepository,
                        this.fieldRepository,
                    ),
                    Shopware.Context.api,
                ).then(() => {
                    this.successMessage = this.$tc(
                        'swag-cms-extensions.sw-cms.elements.custom-form.createTemplateModal.successMessage',
                    );
                    this.showSaveSuccess = true;
                });
            });
        },

        onOverwriteSelectChange(id, form) {
            if (!form) {
                this.templateName = '';
                return;
            }

            this.templateName = form.technicalName;
        },

        openOverwriteContent() {
            this.showOverwriteSelect = true;
        },
    },
});
