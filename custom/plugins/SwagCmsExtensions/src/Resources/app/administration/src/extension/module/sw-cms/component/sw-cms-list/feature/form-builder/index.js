const { Component } = Shopware;
const { Criteria } = Shopware.Data;
const utils = Shopware.Utils;

if (Shopware.Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_63')) {
    Component.override('sw-cms-list', {
        data() {
            return {
                cmsExtensionsBeforeSaveActionIsComplete: false,
            }
        },

        methods: {
            async getPageWithChildren(page) {
                const criteria = (new Criteria())
                    .addAssociation('sections.blocks.slots.swagCmsExtensionsForm');

                return this.pageRepository.get(page.id, Shopware.Context.api, criteria);
            },

            async prepareCmsExtensionsDuplicateCmsPage(page, behavior = { overwrites: {} }) {
                const affectedCmsPage = page.cmsPage || page;

                const pageWithAssociations = await this.getPageWithChildren(affectedCmsPage);
                const sectionOverrides = pageWithAssociations.sections.reduce((accumulator, section, index) => {
                    // The clone route compares the behavior with uuid-sorted database entries. Therefore, we must sort here as well.
                    const sortedBlocks = [...section.blocks].sort((a, b) => a.id.localeCompare(b.id));
                    const blockOverrides = sortedBlocks.reduce((sectionAccumulator, block, blockIndex) => {
                        if (block.type === 'custom-form') {
                            const swagCmsExtensionsForm = block.slots[0].extensions.swagCmsExtensionsForm;
                            const technicalNameOverride = this.createCloneName(swagCmsExtensionsForm.technicalName);
                            const mailTemplateId = swagCmsExtensionsForm.mailTemplateId;
                            sectionAccumulator[blockIndex] = this.createCustomFormDuplicateOverride(technicalNameOverride, mailTemplateId);
                        }

                        return sectionAccumulator;
                    }, {});

                    if (Object.values(blockOverrides).length >= 1) {
                        accumulator[index] = {
                            blocks: blockOverrides,
                        };
                    }

                    return accumulator;
                }, {});

                if (Object.values(sectionOverrides).length <= 0) {
                    return this.$super('onDuplicateCmsPage', page, behavior);
                }

                if (!behavior.overwrites) {
                    behavior.overwrites = {};
                }

                behavior.cloneChildren = true;
                behavior.overwrites.sections = sectionOverrides;

                return this.$super('onDuplicateCmsPage', page, behavior);
            },

            onDuplicateCmsPage(item, behavior = { overwrites: {} }) {
                if (this.cmsExtensionsBeforeSaveActionIsComplete) {
                    this.cmsExtensionsBeforeSaveActionIsComplete = false;
                    this.$super('onDuplicateCmsPage', item, behavior);
                } else {
                    void this.prepareCmsExtensionsDuplicateCmsPage(item, behavior);
                }
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
