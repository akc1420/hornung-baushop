Shopware.Service('privileges')
    .addPrivilegeMappingEntry({
        category: 'permissions',
        parent: null,
        key: 'sales_channel',
        roles: {
            viewer: {
                privileges: [
                    'swag_social_shopping_sales_channel:read',
                    'swag_social_shopping_product_error:read',
                    'sales_channel_type:read',
                    'seo_url:read',
                    'product_visibility:read',
                ],
                dependencies: [],
            },
            editor: {
                privileges: [
                    'swag_social_shopping_sales_channel:update',
                ],
                dependencies: [
                    'sales_channel.viewer',
                ],
            },
            creator: {
                privileges: [
                    'swag_social_shopping_sales_channel:create',
                ],
                dependencies: [
                    'sales_channel.viewer',
                    'sales_channel.editor',
                ],
            },
        },
    });
Shopware.Service('privileges')
    .addPrivilegeMappingEntry({
        category: 'permissions',
        parent:  null,
        key: 'order',
        roles: {
            viewer: {
                privileges: [
                    'swag_social_shopping_order:read',
                    'sales_channel_type:read',
                ],
                dependencies: [],
            },
        },
    });
Shopware.Service('privileges')
    .addPrivilegeMappingEntry({
        category: 'permissions',
        parent:  null,
        key: 'customer',
        roles: {
            viewer: {
                privileges: [
                    'swag_social_shopping_customer:read',
                    'sales_channel_type:read',
                ],
                dependencies: [],
            },
        },
    });
