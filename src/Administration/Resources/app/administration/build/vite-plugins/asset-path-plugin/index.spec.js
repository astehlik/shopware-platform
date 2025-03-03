/**
 * @sw-package framework
 */
import AssetPathPlugin from './index';

describe('build/vite-plugins/asset-path-plugin', () => {
    it('should be a function', () => {
        expect(typeof AssetPathPlugin).toBe('function');
    });

    it('should return plugin', async () => {
        const plugin = AssetPathPlugin('testbundle');

        // Identify plugin by name
        expect(plugin).toHaveProperty('name');
        expect(plugin.name).toBe('shopware-vite-plugin-asset-path');

        // Check if the plugin has a closeBundle method
        expect(plugin).toHaveProperty('renderChunk');
        expect(typeof plugin.renderChunk).toBe('function');

        // Test renderChunk method does not modify code if it does not contain the module preload function
        expect(plugin.renderChunk('import foo from "./bar";')).toBe(null);

        // Test renderChunk method modifies code if it contains the module preload function
        const code = 'const assetsURL = function(dep) { return "/bundles/testbundle/administration/"+dep };';
        const modified =
            'const assetsURL = function(dep) { return window.__sw__.assetPath+"/bundles/testbundle/administration/"+dep };';
        expect(plugin.renderChunk(code)).toEqual({ code: modified, map: null });
    });
});
