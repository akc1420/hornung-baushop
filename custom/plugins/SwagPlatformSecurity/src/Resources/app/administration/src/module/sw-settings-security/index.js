const { Module } = Shopware;

import './page/sw-settings-security-view';
import './extension/sw-settings-index';

const options = {
    type: 'plugin',
    name: 'settings-security',
    title: 'sw-settings-security.general.mainMenuItemGeneral',
    description: 'sw-settings-security.general.description',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#9AA8B5',
    icon: 'default-action-settings',
    favicon: 'icon-module-settings.png',

    routes: {
        index: {
            component: 'sw-settings-security-view',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index',
                privilege: 'admin'
            }
        }
    },

    settingsItem: [
        {
            group: 'plugins',
            to: 'sw.settings.security.index',
            icon: 'default-object-shield',
            name: 'sw-settings-security.general.mainMenuItemGeneral'
        }
    ]
};

if (!Shopware.Component.getComponentRegistry().has('sw-extension-config')) {
    delete options.settingsItem;
}

Module.register('sw-settings-security', options);
