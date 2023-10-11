import template from './sw-product-modal-delivery.html.twig';
import enGB from "../../snippet/en-GB";
import deDE from "../../snippet/de-DE";

// Override Property Detail Page
Shopware.Component.override('sw-product-modal-delivery', {
    template,
    snippets: {
        'en-GB': enGB,
        'de-DE': deDE
    }
});
