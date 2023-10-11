import deDE from '../next20305/sw-users-permissions/snippet/de-DE.json';
import enGB from '../next20305/sw-users-permissions/snippet/en-GB.json';

if (Shopware.Service('swagSecurityState').isActive('NEXT-20305')) {
    Shopware.Service('privileges').addPrivilegeMappingEntry({
        category: 'permissions',
        parent: null,
        key: 'order',
        roles: {
            creator: {
                privileges: ['api_proxy_switch-customer']
            }
        }
    });

    Shopware.Locale.extend('de-DE', deDE);
    Shopware.Locale.extend('en-GB', enGB);
}
