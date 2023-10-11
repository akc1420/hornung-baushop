import './component/kzph-simplenote-note';
import './component/kzph-simplenote-dashboard';

import './module/sw-dashboard/page/sw-dashboard-index';
import './module/sw-order/sw-order-list';
import './module/sw-order/sw-order-detail';
import './module/sw-order/component/sw-order-create-details-header';
import './module/sw-order/component/sw-order-product-select';
import './module/sw-customer/page/sw-customer-detail';
import './module/sw-customer/page/sw-customer-list';
import './module/sw-product/page/sw-product-detail';
import './module/sw-product/page/sw-product-list';
import './module/sw-category/component/sw-category-view';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Shopware.Module.register('kzph-simplenote-tab-note', {
    routeMiddleware(next, currentRoute) {
        
        if (currentRoute.name === 'sw.order.detail') {
            currentRoute.children.push({
                name: 'sw.order.detail.note',
                isChildren: true,
                path: '/sw/order/detail/:id/note',
                component: 'kzph-simplenote-note',
                meta: {
                    parentPath: "sw.order.index",
                    entityType: 'order'
                }
            });
        }

        if (currentRoute.name === 'sw.customer.detail') {
            currentRoute.children.push({
                name: 'sw.customer.detail.note',
                isChildren: true,
                path: '/sw/customer/detail/:id/note',
                component: 'kzph-simplenote-note',
                meta: {
                    parentPath: "sw.customer.index",
                    entityType: 'customer'
                }
            });
        }

        if (currentRoute.name === 'sw.product.detail') {
            currentRoute.children.push({
                name: 'sw.product.detail.note',
                isChildren: true,
                path: '/sw/product/detail/:id/note',
                component: 'kzph-simplenote-note',
                meta: {
                    parentPath: "sw.product.index",
                    entityType: 'product'
                }
            });
        }

        if (currentRoute.name === 'sw.category.detail') {
            currentRoute.children.push({
                name: 'sw.category.detail.note',
                path: '/sw/category/index/:id/note',
                component: 'kzph-simplenote-note',
                meta: {
                    parentPath: "sw.category.index",
                    entityType: 'category'
                }
            });
        }

        next(currentRoute);
    }
});