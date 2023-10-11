// For a detailed explanation regarding each configuration property, visit:
// https://jestjs.io/docs/en/configuration.html

const { resolve } = require('path');

const admin_path = process.env.ADMIN_PATH || resolve('../../../../../../../../../../platform/src/Administration/Resources/app/administration');

process.env.ADMIN_PATH = admin_path;


module.exports = {
    preset: '@shopware-ag/jest-preset-sw6-admin',
    globals: {
        adminPath: admin_path, // required, e.g. /www/sw6/platform/src/Administration/Resources/app/administration
    },

    moduleNameMapper: {
        '^test(.*)$': '<rootDir>/test$1',
        vue$: 'vue/dist/vue.common.dev.js',
    },
};
