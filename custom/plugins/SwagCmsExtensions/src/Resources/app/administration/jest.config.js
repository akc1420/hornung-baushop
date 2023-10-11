const { join, resolve } = require('path');

process.env.ADMIN_PATH = process.env.ADMIN_PATH || resolve('../../../../../../../src/Administration/Resources/app/administration');

module.exports = {
    preset: '@shopware-ag/jest-preset-sw6-admin',
    globals: {
        adminPath: process.env.ADMIN_PATH,
    },

    setupFilesAfterEnv: [
        resolve(join(process.env.ADMIN_PATH, '/test/_setup/prepare_environment.js')),
    ],

    moduleNameMapper: {
        '^test(.*)$': '<rootDir>/test$1',
        vue$: 'vue/dist/vue.common.dev.js',
    },
};
