Shopware.Service('privileges')
    .addPrivilegeMappingEntry({
        category: 'permissions',
        parent: 'settings',
        key: 'rule',
        roles: {
            viewer: {
                privileges: [
                    'cms_block:read',
                    'cms_section:read',
                    'cms_page:read',
                    'swag_cms_extensions_block_rule:read',
                ],
            },
            editor: {
                privileges: [
                    'cms_block:update',
                    'swag_cms_extensions_block_rule:update',
                ],
            },
        },
    });
