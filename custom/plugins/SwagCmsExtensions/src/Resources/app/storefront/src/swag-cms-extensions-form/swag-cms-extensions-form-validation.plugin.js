import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import Iterator from 'src/helper/iterator.helper';

/**
 * This plugin checks for validation fields of a form.
 * It styles the field elements with the bootstrap style and adds the custom error message.
 *
 * Usage:
 *
 * <input data-form-validation-error-message="the content of this field is not correct">
 */
export default class SwagCmsExtensionsFormValidation extends Plugin {

    static options = {

        /**
         * class to add when the field should have styling
         */
        styleClass: 'was-validated',

        /**
         * attribute with message content
         */
        messageAttr: 'data-form-validation-error-message',
    };

    init() {
        if (this._isFormElement() === false) {
            throw Error('Element is not of type <form>');
        }

        this._setNoValidate();
        this._registerEvents();
    }

    /**
     * Verify whether the plugin element is of type <form> or not
     *
     * @returns {boolean}
     *
     * @private
     */
    _isFormElement() {
        return (this.el.tagName.toLowerCase() === 'form');
    }

    /**
     * Prepares the form for custom Bootstrap form validation
     *
     * @private
     */
    _setNoValidate() {
        this.el.setAttribute('novalidate', '');
    }

    /**
     * Registers all needed events
     *
     * @private
     */
    _registerEvents() {
        this.el.addEventListener('submit', this._onFormSubmit.bind(this));

        this._registerValidationListener(this._onValidate.bind(this), ['change', 'input']);
    }

    /**
     * @param {function} listener
     * @param {Event} events
     *
     * @private
     */
    _registerValidationListener(listener, events) {
        const fields = DomAccess.querySelectorAll(this.el, `[${this.options.messageAttr}]`, false);
        if (fields) {
            Iterator.iterate(fields, field => {
                Iterator.iterate(events, event => {
                    field.removeEventListener(event, listener);
                    field.addEventListener(event, listener);
                });
            });
        }
    }

    /**
     * Checks form validity before submit
     *
     * @param {Event} event
     *
     * @private
     */
    _onFormSubmit(event) {
        const validity = this.el.checkValidity();
        if (validity === false) {
            event.preventDefault();
            event.stopPropagation();
        }

        const fields = this.el.querySelectorAll(`[${this.options.messageAttr}]`);

        Iterator.iterate(fields, field => {
            this._resetInvalidMessage(field);
        });

        this.el.classList.add(this.options.styleClass);

        this.$emitter.publish('onFormSubmit', { validity });
        this.$emitter.publish('beforeSubmit');
    }

    /**
     * remove custom validation message
     * and use browser validation
     *
     * @param {Event} event
     *
     * @private
     */
    _onValidate(event) {
        this._resetInvalidMessage(event.target);
    }

    /**
     * shows or hides the custom validation message
     *
     * @param {HTMLElement} field
     *
     * @private
     */
    _resetInvalidMessage(field) {
        const parent = field.parentElement;

        let message = DomAccess.getDataAttribute(field, this.options.messageAttr, false);

        if (field.checkValidity()) {
            message = DomAccess.querySelector(parent, '.js-validation-message', false);
            if (message) {
                message.remove();
            }

            return;
        }

        if (message && !parent.querySelector('.js-validation-message')) {
            parent.insertAdjacentHTML('beforeEnd', `<div class="invalid-feedback js-validation-message">${message}</div>`);
        }
    }
}
