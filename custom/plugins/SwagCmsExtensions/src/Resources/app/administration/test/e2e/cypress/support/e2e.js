// Import commands.js using ES2015 syntax:
import './commands'

import 'cypress-file-upload';
import 'cypress-real-events/support';

require('@shopware-ag/e2e-testsuite-platform/cypress/support');

beforeEach(() => {
    if (!Cypress.env('SKIP_INIT')) {
        return cy.setToInitialState();
    }
});
