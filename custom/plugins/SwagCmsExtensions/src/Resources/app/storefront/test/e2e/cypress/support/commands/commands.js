const ProductFixture = global.ProductFixtureService;

/**
 * Create custom product fixture using Shopware API at the given endpoint
 *
 * @memberOf Cypress.Chainable#
 * @name createQuickviewProductFixture
 * @function
 * @param {Object} [userData={}] - Options concerning creation
 * @param {String} [templateFixtureName='product'] - Specifies the base fixture name
 * @param {String} [categoryName='Home'] - Specifies the category by name of the affected product to set its visibility
 */
Cypress.Commands.add('createQuickviewProductFixture', (userData = {}, templateFixtureName = 'product', categoryName = 'Home') => {
    const fixture = ProductFixture;

    return cy.fixture(templateFixtureName).then((result) => {
        return Cypress._.merge(result, userData);
    }).then((data) => {
        return fixture.setProductFixture(data, categoryName);
    });
});

/**
 * Navigates to the next product in the carousel and waits for its animation to be finished
 *
 * @memberOf Cypress.Chainable#
 * @name quickviewNavigate
 * @function
 * @param {String} [direction='right'] - Only accepts 'left' and 'right' to determine the carousel's rotation direction
 */
Cypress.Commands.add('quickviewNavigate', (direction = 'right') => {
    const directionSelector = `.carousel-control-${direction === 'left' ? 'prev' : 'next'}`;

    cy.get(directionSelector)
        .click();

    cy.get('.carousel-item-left')
        .should('not.exist');
    return cy.get('.carousel-item-right')
        .should('not.exist');
})

/**
 * Assertion, which checks if the given element is visible within the given viewport
 *
 * @function
 * @memberOf Cypress.Chainable#
 * @name isAtLeastPartiallyWithinViewport
 * @param {Object} [viewport={}] - When using the cy.viewport function, you have to provide the differing viewport information here
 * @param {int} [boundTolerance=1] - Pixel tolerance when checking position
 */
Cypress.Commands.add('isAtLeastPartiallyWithinViewport', {
    prevSubject: true
}, (subject, viewport = {}, boundTolerance = 1) => {
    expect(elementIsAtLeastPartiallyWithinViewport(subject[0], viewport, boundTolerance))
        .to.equal(true, 'element is at least partially within viewport');

    return subject;
});

/**
 * Assertion, which checks if the given element is not visible within the given viewport
 *
 * @function
 * @memberOf Cypress.Chainable#
 * @name isNotAtLeastPartiallyWithinViewport
 * @param {Object} [viewport={}] - When using the cy.viewport function, you have to provide the differing viewport information here
 * @param {int} [boundTolerance=1] - Pixel tolerance when checking position
 */
Cypress.Commands.add('isNotAtLeastPartiallyWithinViewport', {
    prevSubject: true
}, (subject, viewport = {}, boundTolerance = 1) => {
    expect(elementIsAtLeastPartiallyWithinViewport(subject[0], viewport, boundTolerance))
        .to.equal(false, 'element is not at least partially within viewport');

    return subject;
});

/**
 * Checks if the given element is visible within the given viewport
 *
 * @param {Element} element - Element to be checked
 * @param {Object} [viewport={}] - Viewport information, contain height and width
 * @param {int} [boundTolerance=1] - Pixel tolerance when checking position
 * @returns {boolean}
 */
function elementIsAtLeastPartiallyWithinViewport(element, viewport = {}, boundTolerance = 1) {
    if (viewport === {}) {
        viewport.width = Cypress.config('viewportWidth');
        viewport.height = Cypress.config('viewportHeight');
    }

    const elementBounds = element.getBoundingClientRect();
    const screenBounds = {
        bottom: viewport.height,
        right: viewport.width
    }

    boundTolerance *= -1;

    const inBounds = {
        top: (elementBounds.top >= boundTolerance) && (elementBounds.top < screenBounds.bottom),
        left: (elementBounds.left >= boundTolerance) && (elementBounds.left < screenBounds.right),
        bottom: (elementBounds.bottom >= boundTolerance) && (elementBounds.bottom < screenBounds.bottom),
        right: (elementBounds.right >= boundTolerance) && (elementBounds.right < screenBounds.right)
    };

    return (inBounds.top || inBounds.bottom) && (inBounds.left || inBounds.right);
}
