import Plugin from 'src/plugin-system/plugin.class';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
import DomAccess from 'src/helper/dom-access.helper';
import Iterator from 'src/helper/iterator.helper';
import HttpClient from 'src/service/http-client.service';
import queryString from 'query-string';

/**
 * this plugin submits the variant form
 * with the correct data options
 */
export default class RecommendyVariantSwitchPlugin extends Plugin {

    static options = {
        url: '',
        elementId: '',
        pageType: '',
        radioFieldSelector: '.product-detail-configurator-option-input',
        selectFieldSelector: '.product-detail-configurator-select-input',
    };

    init() {
        this._httpClient = new HttpClient();
        this._radioFields = DomAccess.querySelectorAll(this.el, this.options.radioFieldSelector, false);
        this._selectFields = DomAccess.querySelectorAll(this.el, this.options.selectFieldSelector, false);
        this._elementId = this.options.elementId;
        this._pageType = this.options.pageType;

        this._ensureFormElement();
        this._preserveCurrentValues();
        this._registerEvents();

    }

    /**
     * ensures that the plugin element is a form
     *
     * @private
     */
    _ensureFormElement() {
        if (this.el.nodeName.toLowerCase() !== 'form') {
            throw new Error('This plugin can only be applied on a form element!');
        }
    }

    /**
     * saves the current value on each form element
     * to be able to retrieve it once it has changed
     *
     * @private
     */
    _preserveCurrentValues() {
        if(this._radioFields) {
            Iterator.iterate(this._radioFields, field => {
                if (RecommendyVariantSwitchPlugin._isFieldSerializable(field)) {
                    if (field.dataset) {
                        field.dataset.variantSwitchValue = field.value;
                    }
                }
            });
        }
    }

    /**
     * register all needed events
     *
     * @private
     */
    _registerEvents() {
        this.el.addEventListener('change', event => this._onChange(event));
    }

    /**
     * callback when the form has changed
     *
     * @param event
     * @private
     */
    _onChange(event) {
        const switchedOptionId = this._getSwitchedOptionId(event.target);
        const selectedOptions = this._getFormValue();
        this._preserveCurrentValues();

        const query = {
            switched: switchedOptionId,
            options: JSON.stringify(selectedOptions),
        };
        this._redirectToVariant(query);
    }

    /**
     * returns the option id of the recently switched field
     *
     * @param field
     * @returns {*}
     * @private
     */
    _getSwitchedOptionId(field) {
        if (!RecommendyVariantSwitchPlugin._isFieldSerializable(field)) {
            return false;
        }

        return field.name.split('_').pop();
    }

    /**
     * returns the current selected
     * variant options from the form
     *
     * @private
     */
    _getFormValue() {
        const serialized = {};
        if(this._radioFields) {
            Iterator.iterate(this._radioFields, field => {
                if (RecommendyVariantSwitchPlugin._isFieldSerializable(field)) {
                    if (field.checked) {
                        serialized[field.name.split('_').pop()] = field.value;
                    }
                }
            });
        }

        if(this._selectFields) {
            Iterator.iterate(this._selectFields, field => {
                if (RecommendyVariantSwitchPlugin._isFieldSerializable(field)) {
                    const selectedOption = [...field.options].find(option => option.selected);
                    serialized[field.name.split('_').pop()] = selectedOption.value;
                }
            });
        }

        return serialized;
    }

    /**
     * checks id the field is a value field
     * and therefore serializable
     *
     * @param field
     * @returns {boolean|*}
     *
     * @private
     */
    static _isFieldSerializable(field) {
        return !field.name || field.disabled || ['file', 'reset', 'submit', 'button'].indexOf(field.type) === -1;
    }

    /**
     * disables all form fields on the form submit
     *
     * @private
     */
    _disableFields() {
        Iterator.iterate(this._radioFields, field => {
            if (field.classList) {
                field.classList.add('disabled', 'disabled');
            }
        });
    }

    /**
     * gets the url of the new variant
     * and redirects to this url
     *
     * @param {Object} data
     * @private
     */
    _redirectToVariant(data) {
        PageLoadingIndicatorUtil.create();

        const requestUrl = DomAccess.getAttribute(this.el, 'action');
        const url =  requestUrl + '?' + queryString.stringify(data);

        this._httpClient.get(url, (response) => {
            let parser = new DOMParser(),
                resDoc = parser.parseFromString(response, "text/html");

            const item = this.el.closest('.recommendy-item');

            const innerItem = DomAccess.querySelector(resDoc, '.recommendy-item', true);

            let accordion = DomAccess.querySelectorAll(resDoc, '.recommendy-collapse', false);
            if(accordion) {
                Iterator.iterate(accordion, field => {
                    field.classList.add('show');
                });
            }

            item.innerHTML = innerItem.innerHTML;
            window.PluginManager.initializePlugins();
            PageLoadingIndicatorUtil.remove();

        });
    }
}
