import FormCsrfHandlerPlugin from 'src/plugin/forms/form-csrf-handler.plugin';

export default class SwagCmsExtensionsFormCsrfHandlerExtension extends FormCsrfHandlerPlugin {
    onSubmit(event) {
        this._validationPluginActive = !!window.PluginManager.getPluginInstanceFromElement(this._form, 'FormValidation')
            || !!window.PluginManager.getPluginInstanceFromElement(this._form, 'SwagCmsExtensionsFormValidation');

        super.onSubmit(event);
    }
}
