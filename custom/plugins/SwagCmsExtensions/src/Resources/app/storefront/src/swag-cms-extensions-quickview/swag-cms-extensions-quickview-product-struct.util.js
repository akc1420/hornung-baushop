export default class ProductStruct {
    /**
     * @param id {string}
     * @param loaded {boolean} Indicates if the content for this product's Quickview was already fetched from the server
     * @param quickview {string} The content of this product's quickview
     */
    constructor(id, loaded, quickview) {
        this._id = id;
        this._variantId = null;
        this._loaded = loaded;
        this._quickview = quickview;
        this._variants = new Map();
    }

    /**
     * @returns {string}
     */
    get id() {
        return this._id;
    }

    /**
     * @returns {string|null}
     */
    get variantId() {
        return this._variantId;
    }

    /**
     * @returns {boolean}
     */
    get loaded() {
        return this._loaded;
    }

    /**
     * @returns {string}
     */
    get quickview() {
        return this._quickview;
    }

    /**
     * @returns {Map<string, ProductStruct>}
     */
    get variants() {
        return this._variants;
    }

    /**
     * @param value {string|null}
     */
    set variantId(value) {
        this._variantId = value;
    }

    /**
     * @param loaded {boolean}
     */
    set loaded(loaded) {
        this._loaded = loaded;
    }

    /**
     * @param quickview {string}
     */
    set quickview(quickview) {
        this._quickview = quickview;
    }
}
