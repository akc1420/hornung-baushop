// / <reference types="Cypress" />
const selector = {
    backButton: '.sw-cms-detail__back-btn',
    loader: '.sw-loader',
    list: {
        defaultLandingPage: '.sw-cms-list-item--0',
        duplicateItem: '.sw-cms-list-item__option-duplicate',
    },
    detail: {
        save: '.sw-cms-detail__save-action',
        block: '.sw-cms-block',
        section: '.sw-cms-section',
        defaultBlock: '.sw-cms-block .sw-text-editor__content-editor',
        addBlockUnderneath: '.sw-cms-stage-add-block:last-of-type',
        blockCategoryDropdown: '#sw-field--currentBlockCategory',
        sectionSelect: '.sw-cms-section__action.sw-cms-section-select',
        customFormBlock: '.swag-cms-extensions-block-custom-form-preview',
        customFormDummy: '.swag-cms-extensions-custom-form-element__dummy',
        blockConfigOverlay: '.sw-cms-block__config-overlay',
        sidebar: {
            addBlockSection: '.sw-sidebar-navigation-item[title="Blocks"]',
            newTextBlock: '.sw-cms-preview-text',
            itemTitle: '.sw-sidebar-item__title',
            blockQuickAction: '.sw-cms-block-config__quickaction',
            sectionQuickAction: '.sw-cms-section-config__quickaction',
        },
        configModal: {
            modal: '.sw-cms-slot__config-modal',
            primaryButton: '.sw-cms-slot__config-modal .sw-button--primary',
        },
        formEditorModal: {
            modal: '.swag-cms-extensions-form-editor-modal',
            technicalNameForm: '.swag-cms-extensions-form-editor-modal__options-technical-name input',
            fieldsTab: '.sw-tabs-item[title="Fields"]',
            createFormButton: '.swag-cms-extensions-form-editor-empty-state__splash-screen .sw-button--small',
            technicalNameField: '.swag-cms-extensions-form-editor-settings-field-type-header__field-technical-name input',
            labelField: '.swag-cms-extensions-form-editor-settings-field-type-header__field-label input',
            primaryButton: '.swag-cms-extensions-form-editor-modal .sw-button--primary',
        },
    }
};

describe('Administration: CMS Extensions', () => {
    beforeEach(() => {
        cy.createCmsFixture().then(() => {
            cy.login();
            cy.visit(`${Cypress.env('admin')}#/sw/cms/index`);
            cy.intercept({
                url: `${Cypress.env('apiPath')}/_action/swag/cms-extensions/form/validateAll`,
                method: 'POST'
            }).as('validateCms');
            cy.intercept({
                url: `${Cypress.env('apiPath')}/search/cms-page`,
                method: 'POST'
            }).as('loadCms');
            cy.intercept({
                url: `${Cypress.env('apiPath')}/_action/clone/cms-block/*`,
                method: 'POST'
            }).as('cloneBlock');
            cy.intercept({
                url: `${Cypress.env('apiPath')}/_action/clone/cms-section/*`,
                method: 'POST'
            }).as('cloneSection');
            cy.intercept({
                url: `${Cypress.env('apiPath')}/_action/clone/cms-page/*`,
                method: 'POST'
            }).as('clonePage');
        });
    });

    it('@package @general: can navigate to a cms detail page, add a block and save', () => {
        cy.get(selector.list.defaultLandingPage)
            .should('exist')
            .click();

        // Visit detail page
        cy.get(selector.detail.defaultBlock)
            .should('exist')
            .contains('Nice slot value');
        cy.get(selector.detail.sidebar.addBlockSection)
            .should('exist')
            .click();
        cy.get(selector.detail.sidebar.newTextBlock)
            .should('exist')
            .dragTo(selector.detail.addBlockUnderneath);
        cy.get(selector.detail.save)
            .click();
        cy.wait('@validateCms');
        cy.wait('@loadCms');
    });

    // TODO CMS-396 - Skipped tue to flakiness
    it.skip('@package @general: can add, configure, save and duplicate elements with a custom form', () => {
        cy.get(selector.list.defaultLandingPage)
            .should('exist')
            .click();

        // Visit detail page
        cy.contains(selector.detail.defaultBlock, 'Nice slot value');
        cy.get(selector.detail.sidebar.addBlockSection)
            .should('exist')
            .click();

        // Go to new Form blocks
        cy.get(selector.detail.blockCategoryDropdown).select('Form');
        cy.get(selector.detail.customFormBlock)
            .should('exist')
            .dragTo(selector.detail.addBlockUnderneath);

        // Get the config modal
        cy.get(selector.detail.configModal.modal)
            .should('exist');
        cy.get(selector.detail.configModal.primaryButton).click();

        // Get the custom form configuration modal
        cy.get(selector.detail.formEditorModal.modal)
            .should('exist');
        cy.get(selector.detail.formEditorModal.technicalNameForm)
            .should('exist')
            .typeAndCheck('Awesome Custom Form');
        cy.get(selector.detail.formEditorModal.fieldsTab).click();
        cy.contains(selector.detail.formEditorModal.createFormButton, 'Create form field')
            .click();

        // Configure fields
        cy.get(selector.detail.formEditorModal.technicalNameField)
            .should('have.value','Field1');
        cy.get(selector.detail.formEditorModal.labelField)
            .typeAndCheck('Cool Label');
        cy.get(selector.detail.formEditorModal.primaryButton).click();

        // Save
        cy.get(selector.detail.save).click();
        cy.wait('@validateCms');
        cy.wait('@loadCms');

        // Check Section and Block count
        cy.get(selector.detail.section).should('have.length', 1);
        cy.get(selector.detail.block).should('have.length', 2);

        // Duplicate block
        cy.get(selector.detail.customFormDummy).should('not.exist');
        cy.get(selector.detail.blockConfigOverlay).eq(1).invoke('show');
        cy.get(selector.detail.blockConfigOverlay).eq(1).should('be.visible').click();

        cy.get(selector.detail.sidebar.itemTitle).contains('Block settings');
        cy.contains(selector.detail.sidebar.blockQuickAction, 'Duplicate')
            .click();

        cy.wait('@cloneBlock').its('response.statusCode')
            .should('be.equal', 200);
        cy.wait('@validateCms');
        cy.wait('@loadCms');

        // Check Section and Block count
        cy.get(selector.detail.section).should('have.length', 1);
        cy.get(selector.detail.block).should('have.length', 3);

        // Clone section
        cy.get(selector.detail.customFormDummy).should('not.exist');
        cy.get(selector.detail.sectionSelect).click();
        cy.get(selector.detail.sidebar.itemTitle).contains('Section settings');
        cy.contains(selector.detail.sidebar.sectionQuickAction, 'Duplicate')
            .click();

        cy.wait('@cloneSection').its('response.statusCode')
            .should('be.equal', 200);
        cy.wait('@validateCms');
        cy.wait('@loadCms');

        // Check Section and Block count
        cy.get(selector.detail.section).should('have.length', 2);
        cy.get(selector.detail.block).should('have.length', 6);

        // Go back to listing
        cy.get(selector.backButton).click();

        // Duplicate page
        cy.get(selector.list.defaultLandingPage)
            .find('.sw-cms-list-item__options.sw-context-button')
            .click({ force: true });
        cy.get(selector.list.duplicateItem).click();
        cy.get(selector.loader).should('not.exist');

        cy.wait('@clonePage').its('response.statusCode')
            .should('be.equal', 200);
        cy.wait('@loadCms');

        cy.contains(selector.list.defaultLandingPage, 'Nice Landingpage - Copy')
            .click();

        // Check Section and Block count
        cy.get(selector.detail.section).should('have.length', 2);
        cy.get(selector.detail.block).should('have.length', 6);
    });
});
