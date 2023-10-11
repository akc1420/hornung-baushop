const { Module } = Shopware;

import PrivilegesService from 'src/app/service/privileges.service.js';

describe('swag-social-shopping', () => {
    beforeAll(() => {
        Shopware.Service().register('privileges', () => new PrivilegesService());
        require('../../src/main.js');
    });

    it('should be registered as a module', () => {
        expect(Module.getModuleRegistry().get('swag-social-shopping')).toBeDefined();
    });
});
