const { Component } = Shopware;
import template from './extension/sw-order-list/sw-order-list.html.twig';
import './component/entity/ott-order-state-select';
import './component/entity/ott-payment-state-select';
import './component/entity/ott-delivery-state-select';
import './service/OttIdealoApiClient';
import './component/ott-idealo-api-test-button';

import localeDE from './snippet/de_DE.json';
import localeEN from './snippet/en_GB.json';
Shopware.Locale.extend('de-DE', localeDE);
Shopware.Locale.extend('en-GB', localeEN);

Component.override('sw-order-list', {
    template,
    computed: {
        orderColumns() {
            const columns = this.getOrderColumns();
            columns.push({
                property: 'customFields.ott_idealo_id',
                dataIndex: 'customFields.ott_idealo_id',
                label: 'Idealo TransaktionsID',
                inlineEdit: 'string',
                allowResize: true,
                align: 'left',
            });

            return columns;
        },
    },
});
