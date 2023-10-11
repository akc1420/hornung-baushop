const uuid = require('uuid/v4');

/* global cy */
const selector = {
    quickview: {
        headline: '.swag-cms-extensions-quickview-header-headline',
        buttonClose: '.swag-cms-extensions-quickview-close-button',
        contentContainer: '.swag-cms-extensions-quickview-container',
        buttonPrevious: '.carousel-control-prev',
        buttonNext: '.carousel-control-next',
        activePrefix: '.active ',
        productId: '[data-swag-cms-extensions-quickview-carousel-product-id]',
        product: {
            name: '[itemprop="name"]',
            price: '.product-detail-price',
            selectQuantity: 'input.product-detail-quantity-input',
            buttonBuy: 'button.btn-buy',
            buttonDetail: 'a.btn.swag-cms-extensions-quickview-detail-page-button'
        }
    }
};

const cmsPageId = uuid().replace(/-/g, '');
const categoryId = uuid().replace(/-/g, '');
const categoryName = 'CmsExtensionsCategory';

describe('Quickview listing', () => {
    beforeEach(() => {
        return cy.setToInitialState().then(() => {
            return cy.createDefaultFixture('cms-page', {
                'id': cmsPageId,
            }, 'cms-page-quickview');
        }).then(() => {
            return cy.createDefaultFixture('category', {
                'id': categoryId,
                'name': categoryName,
                'cmsPageId': cmsPageId
            });
        }).then(() => {
            return cy.createDefaultFixture('system-config');
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
            return cy.createQuickviewProductFixture({}, 'product', categoryName);
        }).then(() => {
            const secondProductData = {
                name: 'Second product',
                productNumber: 'RS-999',
                price: [{
                    currencyId: "b7d2554b0ce847cd82f3ac9bd1c0dfca",
                    net: 16.80,
                    linked: false,
                    gross: 20
                }]
            };

            return cy.createQuickviewProductFixture(secondProductData, 'product', categoryName);
        });
    });

    Object.entries({
        cmsListing: '/',
        search: '/search?search=product'
    }).forEach(([quickviewCase, target]) => {
        it(`Case '${quickviewCase}': Shows a quickview when a product name is clicked and has a working carousel`, () => {
            cy.visit(target);

            switch (quickviewCase) {
                case 'cmsListing':
                    cy.get('.search-headline')
                        .should('not.exist');
                    break;
                case 'search':
                    cy.get('.search-headline')
                        .contains('2 products found for "product"');
                    break;
            }

            cy.contains('Product name').click();

            // First product
            cy.get(selector.quickview.buttonClose)
                .should('exist');

            cy.get(selector.quickview.contentContainer)
                .should('be.visible');

            cy.get(selector.quickview.buttonPrevious)
                .should('be.visible');

            cy.get(selector.quickview.buttonNext)
                .should('be.visible');

            cy.get(selector.quickview.headline)
                .contains('Product name');

            cy.get(selector.quickview.product.price)
                .contains('€10.00');

            cy.get(selector.quickview.contentContainer).should('be.visible');

            cy.get(selector.quickview.contentContainer).within(() => {
                cy.get(selector.quickview.product.selectQuantity)
                    .should('be.visible');

                cy.get(selector.quickview.product.buttonBuy)
                    .should('be.visible');

                cy.get(selector.quickview.product.buttonDetail)
                    .should('be.visible');
            });

            cy.quickviewNavigate('right');

            // Second product -> rotate right
            cy.get(selector.quickview.buttonClose)
                .should('exist');

            cy.get(selector.quickview.contentContainer)
                .should('be.visible');

            cy.get(selector.quickview.buttonPrevious)
                .should('be.visible');

            cy.get(selector.quickview.buttonNext)
                .should('be.visible');

            cy.get(selector.quickview.activePrefix + selector.quickview.headline)
                .contains('Second product');

            cy.get(selector.quickview.activePrefix + selector.quickview.product.price)
                .contains('€20.00');

            // Rotate back to first
            cy.quickviewNavigate('left');

            cy.get(selector.quickview.activePrefix + selector.quickview.headline)
                .contains('Product name');

            cy.get(selector.quickview.activePrefix + selector.quickview.product.price)
                .contains('€10.00');

            // Rotate backwards to second again
            cy.quickviewNavigate('left');

            cy.get(selector.quickview.activePrefix + selector.quickview.headline)
                .contains('Second product');

            cy.get(selector.quickview.activePrefix + selector.quickview.product.price)
                .contains('€20.00');

            // Rotate backwards to first again
            cy.quickviewNavigate('left');

            cy.get(selector.quickview.activePrefix + selector.quickview.headline)
                .contains('Product name');

            cy.get(selector.quickview.activePrefix + selector.quickview.product.price)
                .contains('€10.00');


            // Rotate to first via double right
            cy.quickviewNavigate('right')
                .quickviewNavigate('right');

            cy.get(selector.quickview.activePrefix + selector.quickview.headline)
                .contains('Product name');

            cy.get(selector.quickview.activePrefix + selector.quickview.product.price)
                .contains('€10.00');
        });
    });
});
