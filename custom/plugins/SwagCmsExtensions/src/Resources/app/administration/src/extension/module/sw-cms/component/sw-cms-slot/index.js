import template from './sw-cms-slot.html.twig';

const { Component, Utils } = Shopware;
const { Criteria } = Shopware.Data;

if (Shopware.Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_63')) {
    Component.override('sw-cms-slot', {
        template,

        inject: [
            'repositoryFactory',
        ],

        data() {
            return {
                showFormCreationModal: false,
                formTemplateId: null,
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

            mailTemplateRepository() {
                return this.repositoryFactory.create('mail_template');
            },

            isElementTypeCustomForm() {
                return this.element.type === 'custom-form';
            },

            modalTitle() {
                if (this.isElementTypeCustomForm) {
                    return this.$tc('swag-cms-extensions.sw-cms.components.sw-cms-slot.formModalTitle');
                }

                return this.$tc('sw-cms.detail.title.elementSettingsModal');
            },

            primaryActionText() {
                if (this.isElementTypeCustomForm) {
                    return this.$tc('swag-cms-extensions.sw-cms.components.sw-cms-slot.formModalPrimary');
                }

                return this.$tc('sw-cms.detail.label.buttonElementSettingsConfirm');
            },

            slotHasFormExtension() {
                return Utils.object.hasOwnProperty(this.element, 'extensions') &&
                    Utils.object.hasOwnProperty(this.element.extensions, 'swagCmsExtensionsForm');
            },
        },

        beforeCreate() {
            if (this.slotHasFormExtension) {
                const criteria = new Criteria(1, 1);
                criteria.setIds([this.element.extensions.swagCmsExtensionsForm.id]);
                criteria.addAssociation('groups.fields');

                this.formRepository.search(criteria, Shopware.Context.api).then((result) => {
                    this.element.extensions.swagCmsExtensionsForm = result.first();
                    Shopware.State.commit(
                        'swCmsDetailCurrentCustomForm/setForm',
                        this.element.extensions.swagCmsExtensionsForm,
                    );
                });
            }
        },

        created() {
            this.createdComponent();
        },

        methods: {
            createdComponent() {
                if (!this.isElementTypeCustomForm) {
                    return;
                }

                if (Utils.object.hasOwnProperty(this.element, 'isDragSorted') && this.element.isDragSorted) {
                    return;
                }

                if (Utils.object.hasOwnProperty(this.element, 'swapped') && this.element.swapped) {
                    this.onSettingsButtonClick();
                    return;
                }

                if (!this.element.isNew()) {
                    return;
                }

                this.onSettingsButtonClick();
            },

            onPrimaryConfirm() {
                if (!this.isElementTypeCustomForm) {
                    this.onCloseSettingsModal();
                    return;
                }

                if (!this.formTemplateId) {
                    const form = this.formRepository.create(Shopware.Context.api);
                    form.receivers = [];

                    // This flag is used to determine if the form should be validated on load or not
                    form.isFirstEdit = true;

                    Shopware.State.commit('swCmsDetailCurrentCustomForm/setForm', form);

                    this.getDefaultMailTemplateId();

                    this.showFormCreationModal = true;
                    return;
                }

                const criteria = new Criteria(1, 1);
                criteria.setIds([this.formTemplateId]);
                criteria.addAssociation('groups.fields');
                criteria.getAssociation('groups').addSorting(Criteria.sort('position'));
                criteria.getAssociation('groups.fields').addSorting(Criteria.sort('position'));

                this.formRepository.get(this.formTemplateId, Shopware.Context.api, criteria).then((result) => {
                    // Set original template as form
                    Shopware.State.commit('swCmsDetailCurrentCustomForm/setForm', result);

                    // Get a clone without any ids and a empty technical name
                    const formTemplate = Shopware.State.getters['swCmsDetailCurrentCustomForm/template'](
                        '',
                        this.formRepository,
                        this.groupRepository,
                        this.fieldRepository,
                    );

                    // Prevent templates from self replicating
                    formTemplate.isTemplate = false;

                    // Prevent null errors
                    if (formTemplate.receivers === null) {
                        formTemplate.receivers = [];
                    }

                    // Prevents the form from being validated on creation
                    formTemplate.isFirstEdit = true;

                    // Set the clone as the form
                    Shopware.State.commit('swCmsDetailCurrentCustomForm/setForm', formTemplate);

                    // Reset formTemplateId and display modal
                    this.formTemplateId = null;
                    this.showFormCreationModal = true;
                });
            },

            onSettingsButtonClick() {
                if (this.slotHasFormExtension) {
                    // If the element is new or has changes compared to the server load local version into store
                    if (this.formRepository.hasChanges(this.element.extensions.swagCmsExtensionsForm)) {
                        Shopware.State.commit(
                            'swCmsDetailCurrentCustomForm/setForm',
                            this.element.extensions.swagCmsExtensionsForm,
                        );
                    } else {
                        const criteria = new Criteria(1, 1);
                        criteria.setIds([this.element.extensions.swagCmsExtensionsForm.id]);
                        criteria.addAssociation('groups.fields');
                        criteria.getAssociation('groups').addSorting(
                            Criteria.sort('position'),
                        );
                        criteria.getAssociation('groups.fields').addSorting(
                            Criteria.sort('position'),
                        );

                        this.formRepository.search(criteria, Shopware.Context.api).then((result) => {
                            this.element.extensions.swagCmsExtensionsForm = result.first();
                            Shopware.State.commit(
                                'swCmsDetailCurrentCustomForm/setForm',
                                this.element.extensions.swagCmsExtensionsForm,
                            );
                        });
                    }
                }

                this.$super('onSettingsButtonClick');
            },

            onSelectElement(elementType) {
                this.$super('onSelectElement', elementType);

                if (elementType !== 'custom-form') {
                    return;
                }

                this.$nextTick(() => {
                    this.element.swapped = true;
                    this.createdComponent();
                });
            },

            onCloseSettingsModal() {
                Shopware.State.commit('swCmsDetailCurrentCustomForm/resetState');
                this.$super('onCloseSettingsModal');
            },

            onSettingsModalClose() {
                this.onCloseSettingsModal();

                const deleteBlock = this.isElementTypeCustomForm && (this.element.isNew() ||
                    (Utils.object.hasOwnProperty(this.element, 'swapped') && this.element.swapped));

                if (deleteBlock) {
                    this.$nextTick(() => {
                        this.$emit('delete-block-id', this.element.blockId);
                    });
                }
            },

            onCloseEditFormModal() {
                this.onCloseSettingsModal();
                this.showFormEditorModal = false;
            },

            onCloseCreateFormModal(persist) {
                if (persist) {
                    if (Utils.object.hasOwnProperty(this.element, 'swapped') && this.element.swapped) {
                        delete this.element.swapped;
                    }

                    this.$set(
                        this.element.extensions,
                        'swagCmsExtensionsForm',
                        Shopware.State.get('swCmsDetailCurrentCustomForm').form,
                    );

                    this.onCloseSettingsModal();

                    this.$nextTick(() => {
                        this.showFormCreationModal = false;
                    });

                    return;
                }

                this.showFormCreationModal = false;
            },

            onFormTemplateIdChange(formTemplateId) {
                this.formTemplateId = formTemplateId;
            },

            getDefaultMailTemplateId() {
                const criteria = new Criteria(1, 1);
                criteria.addFilter(
                    Criteria.multi('and', [
                        Criteria.equals('mailTemplateTypeId', '7072eded48ee479185c4a51ff4c9634d'),
                        Criteria.equals('systemDefault', true),
                    ]),
                );

                this.mailTemplateRepository.search(criteria, Shopware.Context.api).then((result) => {
                    if (result.total <= 0) {
                        return;
                    }

                    Shopware.State.commit('swCmsDetailCurrentCustomForm/setFormProperty',
                        {
                            property: 'mailTemplateId',
                            value: result.first().id,
                        });
                });
            },
        },
    });
}
