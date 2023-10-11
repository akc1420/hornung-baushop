import SwagCmsExtensionsQuickview
    from './swag-cms-extensions-quickview/swag-cms-extensions-quickview.plugin';

import SwagCmsExtensionsCrossSellingExtension
    from './plugin-extensions/cross-selling/swag-cms-extensions-cross-selling-extension.plugin';
import SwagCmsExtensionsListingPluginExtension
    from './plugin-extensions/listing/swag-cms-extensions-listing-extension.plugin';
import SwagCmsExtensionsVariantSwitchExtension
    from './plugin-extensions/variant-switch/swag-cms-extensions-variant-switch-extension.plugin';
import SwagCmsExtensionsFormCsrfHandlerExtension
    from './plugin-extensions/form-csrf-handler/swag-cms-extensions-form-csrf-handler-extension.plugin';

import SwagCmsExtensionsFormValidation
    from './swag-cms-extensions-form/swag-cms-extensions-form-validation.plugin';
import SwagCmsExtensionsScrollNavigation
    from './swag-cms-extensions-scroll-navigation/swag-cms-extensions-scroll-navigation.plugin';
import SwagCmsExtensionsScrollNavigationToggleMenu
    from './swag-cms-extensions-scroll-navigation/swag-cms-extensions-scroll-navigation-toggle-menu.plugin';

function registerPlugins(manager) {
    manager.register(
        'SwagCmsExtensionsQuickview',
        SwagCmsExtensionsQuickview,
        '[data-swag-cms-extensions-quickview="true"]',
    );

    manager.register(
        'SwagCmsExtensionsScrollNavigation',
        SwagCmsExtensionsScrollNavigation,
        '[data-swag-cms-extensions-scroll-navigation="true"]',
    );

    manager.register(
        'SwagCmsExtensionsScrollNavigationToggleMenu',
        SwagCmsExtensionsScrollNavigationToggleMenu,
        '[data-swag-cms-extensions-scroll-navigation-toggle-menu="true"]',
    );
    manager.register(
        'SwagCmsExtensionsFormValidation',
        SwagCmsExtensionsFormValidation,
        '[data-swag-cms-extensions-form-validation="true"]',
    );
}

function overridePlugins(manager) {
    manager.override(
        'CrossSelling',
        SwagCmsExtensionsCrossSellingExtension,
        '[data-cross-selling]',
    );

    manager.override(
        'Listing',
        SwagCmsExtensionsListingPluginExtension,
        '[data-listing]',
    );

    manager.override(
        'VariantSwitch',
        SwagCmsExtensionsVariantSwitchExtension,
        '[data-variant-switch]',
    );
    if (window.csrf.enabled && window.csrf.mode === 'ajax') {
        manager.override(
            'FormCsrfHandler',
            SwagCmsExtensionsFormCsrfHandlerExtension,
            '[data-form-csrf-handler]',
        );
    }
}

registerPlugins(window.PluginManager);
overridePlugins(window.PluginManager);

if (module.hot) {
    module.hot.accept();
}
