const createId = require('uuid/v4');

const selector = {
    sidebar: {
        menu: '.scroll-navigation-sidebar',
        entryBullet: '.scroll-navigation-sidebar-entry-bullet',
        entryLabel: '.scroll-navigation-sidebar-entry-label',
    },
    mobile: {
        menuBar: '.scroll-navigation-sidebar-mobile-menu',
        menuOpenButton: '#scroll-navigation-mobile-button-list',
        menuCloseButton: '.scroll-navigation-sidebar-close',
    }
};

const className = {
    sidebar: {
        entry: 'scroll-navigation-sidebar-entry',
        entryActive: 'scroll-navigation-sidebar-entry--active',
    }
};

const color = {
    menuLabelActive: 'rgb(0, 132, 144)',
    menuLabelInactive: 'rgb(74, 84, 91)'
};

const contexts = [
    {
        deviceType: 'desktop',
        viewport: {
            // 'macbook-11' preset
            width: 1366,
            height: 768
        },
        scrollY: [2500, 5000, 7500, 0]
    },
    {
        deviceType: 'tablet',
        viewport: {
            // 'ipad-2' preset
            width: 768,
            height: 1024
        },
        scrollY: [5000, 10000, 15000, 0]
    },
    {
        deviceType: 'mobile',
        viewport:
        {
            // 'iphone-3' preset
            width: 320,
            height: 480
        },
        scrollY: [11000, 22000, 33000, 0]
    },
];

let smoothScrolling;
let categoryId = '';
let cmsPageId = '';

describe('Scroll Navigation', () => {
    beforeEach(() => {
        categoryId = createId().replace(/-/g, '');
        cmsPageId = createId().replace(/-/g, '');

        return cy.setToInitialState().then(() => {
            return cy.createDefaultFixture('cms-page', {
                'id': cmsPageId
            });
        }).then((cmsPageFixture) => {
            smoothScrolling = cmsPageFixture.swagCmsExtensionsScrollNavigationPageSettings;

            return cy.createDefaultFixture('category', {
                'id': categoryId,
                'cmsPageId': cmsPageId
            });
        }).then(() => {
            return cy.searchViaAdminApi({
                endpoint: 'sales-channel',
                data: {
                    field: 'name',
                    value: 'Storefront'
                }
            });
        }).then((salesChannelSearchResult) => {
            return cy.updateViaAdminApi('sales-channel', salesChannelSearchResult.id, {
                data: {
                    navigationCategoryId: categoryId
                }
            });
        }).then(() => {
            cy.setCookie('cookie-preference', '1')
                .visit('/');
        });
    });

    context('shows navigation menu/sidebar when visiting the landing page', () => {
        contexts.forEach((context) => {
            it(`on ${context.deviceType}`, () => {
                cy.viewport(context.viewport.width, context.viewport.height);

                cy.get(selector.sidebar.menu)
                    .should('be.visible');

                if (context.deviceType !== 'desktop') {
                    cy.get(selector.mobile.menuBar)
                        .should('be.visible');

                    cy.get(selector.mobile.menuOpenButton)
                        .should('be.visible')
                        .click();
                }

                cy.get(selector.mobile.menuBar)
                    .should('not.be.visible');

                cy.get('[href="#nav-rose"]')
                    .should('be.visible');
                cy.get('[href="#nav-rose"]')
                    .should('have.class', className.sidebar.entry)
                    .should('have.class', className.sidebar.entryActive);

                cy.get('[href="#nav-beautiful-lavender"]')
                    .should('be.visible');
                cy.get('[href="#nav-beautiful-lavender"]')
                    .should('have.class', className.sidebar.entry)
                    .should('not.have.class', className.sidebar.entryActive);

                cy.get('[href="#nav-somewhat-pinkish"]')
                    .should('be.visible');
                cy.get('[href="#nav-somewhat-pinkish"]')
                    .should('have.class', className.sidebar.entry)
                    .should('not.have.class', className.sidebar.entryActive);

                if (context.deviceType !== 'desktop') {
                    cy.get(selector.mobile.menuCloseButton)
                        .should('be.visible')
                        .click()
                        .should('not.be.visible');

                    cy.get(selector.mobile.menuBar)
                        .should('be.visible');
                }
            });
        })
    });

    context('changes the bullets on scrolling manually', () => {
        contexts.forEach((context) => {
            it(`on ${context.deviceType}`, () => {
                cy.server();
                cy.route({
                    url: `${Cypress.env('apiPath')}/cms-page`,
                    method: 'post'
                }).as('cmsPageLoaded');

                cy.route({
                    url: `${Cypress.env('apiPath')}/category`,
                    method: 'post'
                }).as('categoryLoaded');

                cy.route({
                    url: `${Cypress.env('apiPath')}/search/sales-channel`,
                    method: 'post'
                }).as('salesChannelLoaded');

                cy.viewport(context.viewport.width, context.viewport.height);

                cy.waitFor('@cmsPageLoaded').then((xhr) => {
                    expect(xhr).to.have.property('status', 204);
                });
                cy.waitFor('@categoryLoaded').then((xhr) => {
                    expect(xhr).to.have.property('status', 204);
                });
                cy.waitFor('@salesChannelLoaded').then((xhr) => {
                    expect(xhr).to.have.property('status', 200);
                });

                if (context.deviceType !== 'desktop') {
                    cy.get(selector.mobile.menuOpenButton)
                        .should('be.visible')
                        .click();
                } else {
                    cy.get('.scroll-navigation-sidebar')
                        .should('be.visible');
                }

                cy.scrollTo(0, context.scrollY[1]);
                cy.visit('/#nav-rose');

                if (context.deviceType === 'desktop') {
                    cy.get('.scroll-navigation-sidebar')
                        .should('be.visible');
                }

                // Second Section without point is visible
                cy.scrollTo(0, context.scrollY[0]);
                cy.contains('.cypress1', 'CypressTest1')
                    .should('be.visible');

                cy.get('[href="#nav-rose"]')
                    .should('have.class', className.sidebar.entryActive);
                cy.get('[href="#nav-beautiful-lavender"]')
                    .should('not.have.class', className.sidebar.entryActive);
                cy.get('[href="#nav-somewhat-pinkish"]')
                    .should('not.have.class', className.sidebar.entryActive);

                // Third Section is visible
                cy.scrollTo(0, context.scrollY[1]);
                cy.contains('.cypress2', 'CypressTest2')
                    .should('be.visible');

                cy.get('[href="#nav-rose"]')
                    .should('not.have.class', className.sidebar.entryActive);
                cy.get('[href="#nav-beautiful-lavender"]')
                    .should('have.class', className.sidebar.entryActive);
                cy.get('[href="#nav-somewhat-pinkish"]')
                    .should('not.have.class', className.sidebar.entryActive);

                // Fourth Section is visible
                cy.scrollTo(0, context.scrollY[2]);
                cy.contains('.cypress3', 'CypressTest3')
                    .should('be.visible');

                cy.get('[href="#nav-rose"]')
                    .should('not.have.class', className.sidebar.entryActive);
                cy.get('[href="#nav-beautiful-lavender"]')
                    .should('not.have.class', className.sidebar.entryActive);
                cy.get('[href="#nav-somewhat-pinkish"]')
                    .should('have.class', className.sidebar.entryActive);

                // First Section is, again, visible
                cy.scrollTo(0, context.scrollY[3]);
                cy.contains('.cypress1', 'CypressTest1')
                    .should('be.visible');

                cy.get('[href="#nav-rose"]')
                    .should('have.class', className.sidebar.entryActive);
                cy.get('[href="#nav-beautiful-lavender"]')
                    .should('not.have.class', className.sidebar.entryActive);
                cy.get('[href="#nav-somewhat-pinkish"]')
                    .should('not.have.class', className.sidebar.entryActive);
            });
        });
    });
});
