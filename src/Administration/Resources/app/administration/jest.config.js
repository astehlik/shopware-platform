/**
 * @sw-package framework
 */

// For a detailed explanation regarding each configuration property, visit:
// https://jestjs.io/docs/en/configuration.html
const { existsSync } = require('fs');
const { join, resolve } = require('path');

process.env.PROJECT_ROOT = process.env.PROJECT_ROOT || process.env.INIT_CWD || '.';
process.env.ADMIN_PATH = process.env.ADMIN_PATH || __dirname;
process.env.TZ = process.env.TZ || 'UTC';

// Check if ADMIN_PATH/test/_helper_/component-imports.js exists
if (!existsSync(join(process.env.ADMIN_PATH, '/test/_helper_/componentWrapper/component-imports.js'))) {
    // eslint-disable-next-line max-len
    throw new Error('Missing required /test/_helper_/componentWrapper/component-imports.js file to run tests. Run `npm run unit-setup` before executing tests, or use `composer run admin:unit`.');
}

process.env.JEST_CACHE_DIR = process.env.JEST_CACHE_DIR || '<rootDir>.jestcache';

const isCi = (() => {
    return process.argv.some((arg) => arg === '--ci');
})();

if (isCi) {
    // eslint-disable-next-line no-console
    console.info('Run Jest in CI mode');
} else {
    // eslint-disable-next-line no-console
    console.info('Run Jest in local mode');
}

module.exports = {
    cacheDirectory: process.env.JEST_CACHE_DIR,
    preset: '@shopware-ag/jest-preset-sw6-admin',
    globals: {
        adminPath: process.env.ADMIN_PATH,
        projectRoot: process.env.PROJECT_ROOT,
    },

    globalTeardown: '<rootDir>test/globalTeardown.js',

    testRunner: 'jest-jasmine2',

    resolver: '<rootDir>/test/_helper_/jest-resolver.js',

    runner: 'groups',

    coverageDirectory: join(process.env.PROJECT_ROOT, '/build/artifacts/jest'),

    collectCoverageFrom: [
        'src/**/*.js',
        'src/**/*.ts',
        '!src/**/*.spec.js',
        '!**/*.d.ts',

        // Exception in the build dir for vite plugins
        'build/vite-plugins/**/*.ts',
        '!build/vite-plugins/**/*.spec.ts',
    ],

    coverageReporters: [
        'text',
        'cobertura',
        'html-spa',
    ],

    setupFilesAfterEnv: [
        'jest-expect-message',
        resolve(join(__dirname, '/test/_setup/prepare_environment.js')),
    ],

    transform: {
        // stringify svg imports
        '.*\\.(svg)$': '<rootDir>/test/transformer/svgStringifyTransformer.js',
        '^.+\\.vue$': "@vue/vue3-jest",
    },

    transformIgnorePatterns: [
        '/node_modules/(?!(@shopware-ag/meteor-component-library|@shopware-ag/meteor-icon-kit|uuidv7|other)/)',
    ],

    moduleNameMapper: {
        '^test(.*)$': '<rootDir>/test$1',
        '^\@shopware-ag\/admin-extension-sdk\/es\/(.*)': '<rootDir>/node_modules/@shopware-ag/admin-extension-sdk/umd/$1',
        '^\@shopware-ag\/meteor-admin-sdk\/es\/(.*)': '<rootDir>/node_modules/@shopware-ag/meteor-admin-sdk/umd/$1',
        '^@shopware-ag/meteor-component-library$': '<rootDir>/node_modules/@shopware-ag/meteor-component-library/dist/common/index.js',
        '^lodash-es$': 'lodash',
        vue$: 'vue/dist/vue.cjs.js',
    },

    reporters: isCi ? [
        [
            'jest-silent-reporter',
            {
                useDots: true,
                showWarnings: true,
                showPaths: true,
            },
        ],
        ['jest-junit', {
            suiteName: 'Shopware 6 Unit Tests',
            outputDirectory: join(process.env.PROJECT_ROOT, '/build/artifacts/jest'),
            outputName: 'administration.junit.xml',
        }],
    ] : [
        'default',
        '<rootDir>/test/_helper_/failedSpecFileReporter.js',
    ],

    testMatch: [
        '<rootDir>/src/**/*.spec.js',
        '<rootDir>/src/**/*.spec.ts',
        '<rootDir>/eslint-rules/**/*.spec.js',
        '<rootDir>/build/vite-plugins/**/*.spec.ts',
        '<rootDir>/build/vite-plugins/**/*.spec.js',
        '!<rootDir>/src/**/*.spec.vue2.js',
        '<rootDir>/scripts/**/*.spec.ts',
    ],

    testEnvironmentOptions: {
        customExportConditions: ['node', 'node-addons'],
    },
};
