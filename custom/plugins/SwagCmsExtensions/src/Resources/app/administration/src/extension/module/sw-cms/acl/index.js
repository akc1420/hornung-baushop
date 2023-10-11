Shopware.Service('privileges')
    .addPrivilegeMappingEntry({
        category: 'permissions',
        parent: 'content',
        key: 'cms',
        roles: {
            viewer: {
                privileges: [
                    'swag_cms_extensions_quickview:read',
                    'swag_cms_extensions_scroll_navigation:read',
                    'swag_cms_extensions_scroll_navigation_page_settings:read',
                    'swag_cms_extensions_block_rule:read',
                ],
                dependencies: [],
            },
            editor: {
                privileges: [
                    'swag_cms_extensions_quickview:update',
                    'swag_cms_extensions_scroll_navigation:update',
                    'swag_cms_extensions_scroll_navigation_page_settings:update',
                ],
                dependencies: [
                    'cms.viewer',
                ],
            },

            creator: {
                privileges: [
                    'swag_cms_extensions_quickview:create',
                    'swag_cms_extensions_scroll_navigation:create',
                    'swag_cms_extensions_scroll_navigation_page_settings:create',
                ],
                dependencies: [
                    'cms.viewer',
                    'cms.editor',
                ],
            },

            deleter: {
                privileges: [
                    'swag_cms_extensions_quickview:delete',
                    'swag_cms_extensions_scroll_navigation:delete',
                    'swag_cms_extensions_scroll_navigation_page_settings:delete',
                ],
                dependencies: [
                    'cms.viewer',
                ],
            },
        },
    });
