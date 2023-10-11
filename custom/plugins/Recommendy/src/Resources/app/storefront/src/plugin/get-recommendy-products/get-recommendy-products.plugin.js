import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import FormSerializeUtil from 'src/utility/form/form-serialize.util';
import HttpClient from 'src/service/http-client.service';
import Iterator from 'src/helper/iterator.helper';

export default class GetRecommendyProductsPlugin extends Plugin {

    static  options = {
        'productCardCol': '.cms-listing-col',
        'newProductCardCol': '.recommendy-listing-col:not(.recommendy-animated)',
        'parentFilterPanelSelector': '.cms-element-product-listing-wrapper',
        'newAddedProductsCol': '.recommendy-listing-col',
        'listProductsChildCol': '.recommendy-default-listing-col'
    }

    init() {
        const me = this;

        let listProducts = document.querySelectorAll(me.options.listProductsChildCol);
        Iterator.iterate(listProducts, listProduct => {
            let cmsListProduct = listProduct.closest(me.options.productCardCol + ':not([data-productid])');
            if (cmsListProduct) {
                cmsListProduct.dataset.productid = listProduct.getAttribute("data-productid");
            }
        });

        this._getForm();
        this.httpClient = new HttpClient();

        const parentFilterPanelElement = DomAccess.querySelector(document, this.options.parentFilterPanelSelector);

        this.listing = window.PluginManager.getPluginInstanceFromElement(
            parentFilterPanelElement,
            'Listing'
        );

        this._registerEvents();
    }

    /**
     * tries to get the closest form
     */
    _getForm() {
        if (this.el && this.el.nodeName === 'FORM') {
            this._form = this.el;
        } else {
            this._form = this.el.closest('form');
        }

        if (!this._form) {
            throw new Error(`No form found for the plugin: ${this.constructor.name}`);
        }
    }

    _registerEvents() {
        this.el.addEventListener('submit', this._formSubmit.bind(this));
    }

    _formSubmit(event) {
        event.preventDefault();

        const me = this;
        const productCardCol = this.el.closest(this.options.productCardCol);
        const similarBtn = productCardCol.querySelector(".recommendy-btn-similar");

        if (similarBtn.classList.contains('disabled')) {
            return;
        }

        similarBtn.classList.add("disabled");

        const requestUrl = DomAccess.getAttribute(this._form, 'action');
        const formData = FormSerializeUtil.serialize(this._form);
        const filters = me._getFilters();

        if (typeof filters === "object") {
            Object.keys(filters).forEach(function (key) {
                formData.append(key, filters[key]);
            });
        }


        if (window._addedProductsQuantity) {
            formData.append("alreadyViewedIds", me._getAlreadyViewedIds.call(me))
        }

        this.httpClient.post(requestUrl, formData, function (res) {
            if (res) {

                let parser = new DOMParser(),
                    resDoc = parser.parseFromString(res, "text/html"),
                    responseList = resDoc.querySelectorAll(me.options.newAddedProductsCol),
                    clickedProductNextSiblings = me.getNextSiblings(productCardCol);

                Iterator.iterate(responseList, responseEl => {
                    const responseElProductId = responseEl.getAttribute("data-productid");

                    Iterator.iterate(clickedProductNextSiblings, nextSibling => {
                        let nextSiblingProductId = nextSibling.getAttribute("data-productid");
                        if (nextSiblingProductId === responseElProductId) {
                            nextSibling.remove();
                        }
                    });
                });

                productCardCol.insertAdjacentHTML('afterEnd', res);

                window.PluginManager.initializePlugins();
                window.setTimeout(me._addAnimatedClass.bind(me), 100);
                me._calculateClonedProductsCount();
            }else{
                similarBtn.classList.add('no-display');

                const uniqueBtn = productCardCol.querySelector(".recommendy-btn-unique");
                if(uniqueBtn){
                    uniqueBtn.classList.remove('no-display');
                }
            }
        })
        this.$emitter.publish('beforeFormSubmit', formData);
    }

    _addAnimatedClass() {
        const newProductCardCols = document.querySelectorAll(this.options.newProductCardCol);

        Iterator.iterate(newProductCardCols, newProductElem => newProductElem.classList.add("recommendy-animated"));
    }

    _getFilters() {
        const filters = this.listing._fetchValuesOfRegisteredFilters();

        return this.listing._mapFilters(filters);
    }

    _calculateClonedProductsCount() {
        const me = this;
        const newAddedProductsCols = document.querySelectorAll(me.options.newAddedProductsCol);

        window._addedProductsQuantity = {};

        Iterator.iterate(newAddedProductsCols, newProductCol => {
            const productId = newProductCol.getAttribute("data-productid");

            window._addedProductsQuantity[productId] ? window._addedProductsQuantity[productId] += 1 : window._addedProductsQuantity[productId] = 1;
        })
    }

    _getAlreadyViewedIds() {
        const duplicateMaxCount = 2;
        const alreadyViewedIds = [];

        Object.keys(window._addedProductsQuantity).forEach(function (productId) {
            if (window._addedProductsQuantity[productId] >= duplicateMaxCount) {
                alreadyViewedIds.push(productId)
            }
        })
        return alreadyViewedIds;
    }

    getNextSiblings(elem, filter) {
        let sibs = [],
            nextElem = elem.parentNode.firstChild;

        do {
            if (nextElem.nodeType === 3) continue; // ignore text nodes
            if (nextElem === elem) continue; // ignore elem of target
            if (nextElem === elem.nextElementSibling) {
                if (!filter || filter(elem)) {
                    sibs.push(nextElem);
                    elem = nextElem;
                }
            }
        } while (nextElem = nextElem.nextSibling)

        return sibs;
    }
}
