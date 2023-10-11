import EventAwareAnalyticsEvent from 'src/plugin/google-analytics/event-aware-analytics-event';

export default class GtmAddToCartEvent extends EventAwareAnalyticsEvent
{
    supports() {
        return true;
    }

    getPluginName() {
        return 'AddToCart';
    }

    getEvents() {
        return {
            'beforeFormSubmit':  this._beforeFormSubmit.bind(this)
        };
    }

    _beforeFormSubmit(event) {
        if (!this.active) {
            return;
        }

        const formData = event.detail;
        let productId = null;

        formData.forEach((value, key) => {
            if (key.endsWith('[id]')) {
                productId = value;
            }
        });

        if (!productId) {
            console.warn('[codiverse GTM] Product ID could not be fetched. Skipping.');
            return;
        }

        let products = this.getProductsObjectFromFormData(formData, productId);

        // Clear the previous ecommerce object
        dataLayer.push({ ecommerce: null });
        dataLayer.push({
            'event': 'add_to_cart',
            'ecommerce': {
                'currency': formData.get('dtgs-gtm-currency-code'),
                'items': [products]
            }
        });
    }

    getProductsObjectFromFormData(formData, productId) {

        //Product Array
        let products = {
            'item_name': formData.get('product-name'),
            'item_id': formData.get('dtgs-gtm-product-sku'),
            'quantity': Number(formData.get('lineItems[' + productId + '][quantity]'))
        };

        //Price und Brand Name optional
        if(formData.get('dtgs-gtm-product-price') !== null) Object.assign(products, {'price': Number(formData.get('dtgs-gtm-product-price'))});
        if(formData.get('brand-name') !== null) Object.assign(products, {'item_brand': formData.get('brand-name')});

        return products;

    }
}
