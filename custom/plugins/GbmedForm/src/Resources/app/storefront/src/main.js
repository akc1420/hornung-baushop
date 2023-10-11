// Import all necessary Storefront plugins and scss files
import GbmedForm from './js/gbmed-form.plugin';

// Register them via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('GbmedForm', GbmedForm, '[data-gbmed-recaptcha]');
