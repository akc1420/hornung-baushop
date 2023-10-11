import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

export default class RecommendyTrackingPlugin extends Plugin {

    static  options = {
        'productCardCol': '.cms-listing-col',
        'cmsListingRow': '.cms-listing-row',
        'productImageLink': 'product-image-link',
        'productNameLink': 'product-name',
        'productBox': '.recommendy-item',
        detailSliderSelectors: [
            '.recommendy-item .product-name',
            '.recommendy-item .product-image-link',
            '.recommendy-item .btn-buy',
            '.recommendy-item .recommendy-product-link'
        ]
    }

    init() {
        const me = this,
            elClassList = me.el.classList,
            isListing = me._isListing(elClassList),
            isDetail = elClassList.contains('is-ctl-product'),
            isCheckout = elClassList.contains('is-ctl-checkout'),
            isSearch = elClassList.contains('is-ctl-search');


        this.httpClient = new HttpClient();

        let item = localStorage.getItem("RecommendyTracking");
        if (item) {
            localStorage.removeItem("RecommendyTracking");
            item = JSON.parse(item);
            this.httpClient.post(item.url, JSON.stringify({
                    productId: item.productId,
                    actionId: item.actionId,
                    price: item.price
                }), function (res) {
                }
            );
        }




        if (isListing) {
            me.cmsListingRow = me.el.querySelector(me.options.cmsListingRow);
            me.registerListingEventListeners();
            return;
        }

        me.registerDetailEventListeners();

    }

    registerListingEventListeners() {
        const me = this;

        if (me.cmsListingRow) {
            me.cmsListingRow.addEventListener("click", me.onClickCmsListingRow.bind(me));
        }
    }

    registerDetailEventListeners() {
        let me = this,
            selectors = this.options.detailSliderSelectors.join(', ');
        document.querySelectorAll(selectors).forEach(item => {
            item.addEventListener('click', event => {
                me.recommendyTracking.call(me, event.target);
            });
        });
    }

    /**
     * @param event
     */
    onClickCmsListingRow(event) {
        let me = this,
            target = event.target;

        if (target.classList.contains(me.options.productImageLink) ||
            target.classList.contains(me.options.productNameLink) ||
            target.closest('.' + me.options.productImageLink) ||
            target.closest('.' + me.options.productNameLink) ||
            target.tagName === 'A' ||
            target.tagName === 'BUTTON'
        ) {
            me.recommendyTracking.call(me, target);
        }

    }

    /**
     * @param target
     */
    recommendyTracking(target) {
        let me = this,
            elClassList = me.el.classList,
            isListing = me._isListing(elClassList),
            isBuyBox = target.classList.contains('btn-buy');

        let parent = isListing ? target.closest(me.options.productCardCol) : isBuyBox ? target.closest('.recommendy-buy-box-container') : target.closest(me.options.productBox);

        let parentRecommendyTracking = parent ? parent.querySelector('input[name="recommendyTracking"]') : null;
        if (!parentRecommendyTracking) {
            return;
        }

        if(isBuyBox){
            if(isListing && !parent.getAttribute('data-recommendyRecommendation')){
                return;
            }

            let url = parentRecommendyTracking.getAttribute('data-recommendy-track-ajaxUrl');
            if (url) {
                let price = parent.querySelector('input[name="recommendyTrackingPrice"]').getAttribute('value');
                let pId = parentRecommendyTracking.getAttribute('value');
                let httpClient = new HttpClient();
                httpClient.post(url, JSON.stringify({
                        productId: pId,
                        actionId: 10,
                        price: price
                    }), function (res) {
                    }
                );

            }
            return;
        }

        let url = parentRecommendyTracking.getAttribute('data-recommendy-track-ajaxUrl');
        let price = parent.querySelector('input[name="recommendyTrackingPrice"]').getAttribute('value');
        if (url) {
            let pId = parentRecommendyTracking.getAttribute('value');
            if (isListing && parent.getAttribute('data-recommendyRecommendation')) {
                localStorage.setItem("RecommendyTracking", JSON.stringify({
                    url: url,
                    productId: pId,
                    actionId: 1,
                    price: price
                }));
            } else {
                let actionId = parent.querySelector('input[name="recommendyTrackingActionId"]');
                if (actionId) {
                    actionId = +actionId.getAttribute('value');
                    if(actionId <= 1) return;
                    localStorage.setItem("RecommendyTracking", JSON.stringify({
                        url: url,
                        productId: pId,
                        actionId: actionId,
                        price: price
                    }));
                }
            }
        }
    }

    /**
     * @param elClassList
     * @returns {*}
     * @private
     */
    _isListing(elClassList) {
        return (elClassList.contains('is-ctl-navigation') && elClassList.contains('is-act-index'))
            || (elClassList.contains('is-ctl-search') && elClassList.contains('is-act-search'));
    }
}
