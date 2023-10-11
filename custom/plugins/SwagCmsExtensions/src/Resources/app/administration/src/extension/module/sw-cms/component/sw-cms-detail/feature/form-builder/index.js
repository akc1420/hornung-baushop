import formState from './state';

const { Component } = Shopware;
const utils = Shopware.Utils;

if (Shopware.Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_63')) {
    Component.override('sw-cms-detail', {
        inject: [
            'SwagCmsExtensionsFormValidationService',
        ],

        beforeCreate() {
            if (Shopware.State.list().indexOf('swCmsDetailCurrentCustomForm') !== -1) {
                Shopware.State.unregisterModule('swCmsDetailCurrentCustomForm');
            }
            Shopware.State.registerModule('swCmsDetailCurrentCustomForm', formState);
            Shopware.State.commit('swCmsDetailCurrentCustomForm/resetState');
        },

        methods: {
            onSave() {
                const forms = [];

                this.page.sections.forEach((section) => {
                    section.blocks.forEach((block) => {
                        block.slots.forEach((slot) => {
                            if (!Shopware.Utils.object.hasOwnProperty(slot, 'extensions') ||
                                !Shopware.Utils.object.hasOwnProperty(slot.extensions, 'swagCmsExtensionsForm')
                            ) {
                                return;
                            }

                            forms.push(slot.extensions.swagCmsExtensionsForm);
                        });
                    });
                });

                return this.SwagCmsExtensionsFormValidationService.validateAllForms(forms).catch(() => {
                    this.createNotificationError({
                        message: this.$tc('swag-cms-extensions.sw-cms.detail.errors.customFormConfigurationInvalid'),
                    });

                    return Promise.reject();
                }).then(() => {
                    return this.$super('onSave');
                });
            },

            async onBlockDuplicate(block, parameters) {
                if (block.type !== 'custom-form') {
                    return this.$super('onBlockDuplicate', block, parameters);
                }

                const swagCmsExtensionsForm = block.slots[0].extensions.swagCmsExtensionsForm;
                const technicalNameOverride = this.createCloneName(swagCmsExtensionsForm.technicalName);
                const mailTemplateId = swagCmsExtensionsForm.mailTemplateId;
                const behavior = {
                    overwrites: {
                        position: block.position + 1,
                        ...this.createCustomFormDuplicateOverride(technicalNameOverride, mailTemplateId),
                    },
                    cloneChildren: true,
                };

                return this.blockRepository.clone(block.id, Shopware.Context.api, behavior).then(async ({ id: clonedBlockId }) => {
                    const clonedBlock = await this.blockRepository.get(clonedBlockId);

                    const section = this.page.sections[parameters.position];
                    section.blocks.splice(clonedBlock.position, 0, clonedBlock);

                    this.updateBlockPositions(section);
                    this.onSave();
                }).catch(() => {
                    this.createNotificationError({
                        message: this.$tc('global.notification.unspecifiedSaveErrorMessage'),
                    });
                });
            },

            async onSectionDuplicate(section) {
                // The clone route compares the behavior with uuid-sorted database entries. Therefore, we must sort here as well.
                const sortedBlocks = [...section.blocks].sort((a,b) => a.id.localeCompare(b.id));
                const blockOverrides = sortedBlocks.reduce((accumulator, block, index) => {
                    if (block.type === 'custom-form') {
                        const swagCmsExtensionsForm = block.slots[0].extensions.swagCmsExtensionsForm;
                        const technicalNameOverride = this.createCloneName(swagCmsExtensionsForm.technicalName);
                        const mailTemplateId = swagCmsExtensionsForm.mailTemplateId;
                        accumulator[index] = this.createCustomFormDuplicateOverride(technicalNameOverride, mailTemplateId);
                    }

                    return accumulator;
                }, {});

                if (Object.values(blockOverrides).length <= 0) {
                    return this.$super('onSectionDuplicate', section);
                }

                const behavior = {
                    overwrites: {
                        position: section.position + 1,
                        blocks: blockOverrides,
                    },
                    cloneChildren: true,
                };
                
                return this.sectionRepository.clone(section.id, Shopware.Context.api, behavior).then(async ({ id: clonedSectionId }) => {
                    const clonedSection = await this.sectionRepository.get(clonedSectionId);
                    this.page.sections.splice(clonedSection.position, 0, clonedSection);

                    this.updateSectionAndBlockPositions(section);
                    this.onSave();
                }).catch(() => {
                    this.createNotificationError({
                        message: this.$tc('global.notification.unspecifiedSaveErrorMessage'),
                    });
                });
            },

            createCloneName(originalName) {
                return `${originalName}_${utils.createId().slice(0, 5)}`;
            },

            createCustomFormDuplicateOverride(technicalNameOverride, mailTemplateId) {
                return {
                    slots: {
                        0: {
                            extensions: {
                                swagCmsExtensionsForm: {
                                    technicalName: technicalNameOverride,
                                    mailTemplateId,
                                },
                            },
                        },
                    },
                };
            },
        },
    });
}
