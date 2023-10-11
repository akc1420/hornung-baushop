import Feature from 'src/helper/feature.helper';

export const SWAG_CMS_EXTENSIONS_QUICKVIEW_CAROUSEL = {
    CAROUSEL_ID: 'swag-cms-extensions-quickview-carousel',
    CAROUSEL_ITEM_CLASS: 'carousel-item',
    CAROUSEL_ITEM_PRODUCT_ID_ATTR: 'data-swag-cms-extensions-quickview-carousel-product-id',
};

export default class CarouselTemplateUtil {
    /**
     * @param arrowHeadLeft {string}
     * @param arrowHeadRight {string}
     * @param carouselId {string|null}
     * @param carouselItemProductIdAttribute {string|null}
     */
    constructor(
        arrowHeadLeft,
        arrowHeadRight,
        carouselId = SWAG_CMS_EXTENSIONS_QUICKVIEW_CAROUSEL.CAROUSEL_ID,
        carouselItemProductIdAttribute = SWAG_CMS_EXTENSIONS_QUICKVIEW_CAROUSEL.CAROUSEL_ITEM_PRODUCT_ID_ATTR
    ) {
        this._arrowHeadLeft = arrowHeadLeft;
        this._arrowHeadRight = arrowHeadRight;
        this._id = carouselId;
        this._carouselItemProductIdAttribute = carouselItemProductIdAttribute;
    }

    /**
     * @param items {array}
     *
     * @returns {string}
     */
    create(items) {
        return `
            <div id="${this._id}" class="carousel slide" data-interval="0">
                <div class="carousel-inner">
                    ${items.join('')}
                </div>
                ${this._createNavigationElement('prev')}
                ${this._createNavigationElement('next')}
            </div>
        `;
    }

    /**
     * @param content {string}
     * @param productId {string}
     * @param active {boolean}
     *
     * @returns {string}
     */
    createItem(content, productId, active = false) {
        return `
            <div class="${SWAG_CMS_EXTENSIONS_QUICKVIEW_CAROUSEL.CAROUSEL_ITEM_CLASS} ${active ? 'active' : ''}"
                ${this._carouselItemProductIdAttribute}="${productId}">
                ${content}
            </div>
        `;
    }

    /**
     * @param direction {'prev'|'next'}
     *
     * @returns {string}
     */
    _createNavigationElement(direction) {
        const isBootstrap5 = Feature.isActive('v6.5.0.0');
        const previous = direction === 'prev';
        const directionControl = isBootstrap5 ? 'data-bs-slide' : 'data-slide';
        return `
            <a class="carousel-control-${direction}" href="#${this._id}" role="button" ${directionControl}=${direction}>
                ${previous ? this._arrowHeadLeft : this._arrowHeadRight}
                <span class="${isBootstrap5 ? 'visually-hidden': 'sr-only'}">${previous ? 'Previous' : 'Next'}</span>
            </a>
        `;
    }
}
