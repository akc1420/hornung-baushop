import Plugin from 'src/plugin-system/plugin.class';

export default class GbmedForm extends Plugin {
    /**
     * init
     */
    init() {
        const me = this;

        if (window.gbmedFormsOptions.sitekey && me.formsExists()) {
            this.loadRecaptcha();
        }
    }

    /**
     * execute on reinit plugin
     * @private
     */
    _update() {
        const me = this;
        if (window.gbmedFormsOptions === undefined || !me.existGrecaptcha()) {
            return;
        }

        window.gbmedFormsOptions.forms.forEach(function(formEl) {
            let form;

            if (NodeList.prototype.isPrototypeOf(formEl) && formEl.length) {
                formEl.forEach(function(el) {
                    form = me.getFormElement(el);
                    if (form) {
                        me.appendToken(form);
                    }
                });
            } else if (!NodeList.prototype.isPrototypeOf(formEl) && formEl.nodeName !== undefined) {
                form = me.getFormElement(formEl);
                if (form) {
                    me.appendToken(form);
                }
            }
        });
    }

    /**
     * add hidden form field g-recaptcha-response
     *
     * @param form
     */
    appendToken(form) {
        if (!this.isFormElement(form)) {
            return;
        }

        const me = this,
            input = document.createElement('input'),
            intervalMinutes = 1000 * 60 * window.gbmedFormsOptions.interval;

        input.setAttribute('type', 'hidden');
        input.setAttribute('name', 'g-recaptcha-response');
        form.appendChild(input);
        me.reCaptchaExecute(input, form);
        setInterval(function() {
                me.reCaptchaExecute(input, form);
            },
            intervalMinutes,
        );

        if (window.gbmedFormsOptions.hideBadge !== undefined && window.gbmedFormsOptions.hideBadge) {
            const badge = document.createElement('div');
            const formRows = form.querySelectorAll('.form-row');
            badge.classList.add('reCaptchaBageHidden');
            badge.classList.add('my-3');
            badge.innerHTML = window.gbmedFormsOptions.hideBadgeText;
            form.appendChild(badge);
        } else {
            me.showReCaptchaBadge();
        }
    }

    /**
     * set hidden form field g-recaptcha-response token value
     *
     * @param input
     * @param form
     */
    reCaptchaExecute(input, form) {
        const me = this;
        const g = this.isEnterprise() ? window.grecaptcha.enterprise : window.grecaptcha;

        g.execute({
            action: me.getReCaptchaAction(form),
        }).then(function(token) {
            input.setAttribute('value', token);
        });
    }

    /**
     * return recaptcha action
     *
     * @param form
     * @return {*|string}
     */
    getReCaptchaAction(form) {
        let action = form.dataset['recaptcha-action'];

        if(action === undefined){
            action = form.action.split('/').pop();
        }

        return action !== undefined && action.length ? action : 'homepage';
    }

    /**
     * view recaptcha badge
     */
    showReCaptchaBadge() {
        const me = this;

        me.el.classList.add('show');
    }

    /**
     * check form element is realy a form node name
     *
     * @param form
     * @return {boolean}
     */
    isFormElement(form) {
        return form.nodeName !== undefined && form.nodeName.toLowerCase() === 'form';
    }

    /**
     * check grecaptcha exist
     *
     * @return {boolean}
     */
    existGrecaptcha() {
        if(this.isEnterprise()){
            return window.grecaptcha !== undefined && typeof window.grecaptcha.enterprise.ready === 'function';
        }

        return window.grecaptcha !== undefined && typeof window.grecaptcha.ready === 'function';
    }

    getFormElement(el) {
        let form = null;
        if (el !== null && !HTMLCollection.prototype.isPrototypeOf(el)) {
            form = el;
        } else if (HTMLCollection.prototype.isPrototypeOf(el) && el.length) {
            form = el[0];
        }

        return form;
    }

    formsExists() {
        const me = this;
        let exist = false;

        for (let el of window.gbmedFormsOptions.forms) {
            if (el && el.length) {
                let form = me.getFormElement(el[0]);
                exist = form && me.isFormElement(form);
                if (exist) {
                    break;
                }
            }
        }

        return exist;
    }

    loadRecaptcha() {
        const me = this;
        const js = document.createElement('script');
        const apiFile = me.isEnterprise() ? 'enterprise' : 'api';
        js.type = 'text/javascript';
        js.src = `https://www.recaptcha.net/recaptcha/${apiFile}.js?hl=${window.gbmedFormsOptions.hl}&onload=gRecaptchaLoadCallback&render=explicit`;
        js.async = true;
        js.defer = true;
        js.onload = function() {
            if (me.isEnterprise()) {
                window.grecaptcha.enterprise.ready(function() {
                    me._update();
                });
                return;
            }
            window.grecaptcha.ready(function() {
                me._update();
            });
        };
        document.body.appendChild(js);
    }

    isEnterprise() {
        return window.gbmedFormsOptions.isEnterprise;
    }
}
