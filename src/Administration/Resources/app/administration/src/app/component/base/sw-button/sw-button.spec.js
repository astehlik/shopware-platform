/**
 * @sw-package framework
 */

import { mount } from '@vue/test-utils';

async function createWrapper() {
    return mount(await wrapTestComponent('sw-button', { sync: true }), {
        global: {
            stubs: {
                'mt-button': true,
                'sw-button-deprecated': true,
            },
        },
    });
}

describe('components/base/sw-button', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();
        expect(wrapper.vm).toBeTruthy();
    });

    it('should render the mt-button when major feature flag is enabled', async () => {
        global.activeFeatureFlags = ['ENABLE_METEOR_COMPONENTS'];

        const wrapper = await createWrapper();

        expect(wrapper.html()).toContain('mt-button');
    });
});
