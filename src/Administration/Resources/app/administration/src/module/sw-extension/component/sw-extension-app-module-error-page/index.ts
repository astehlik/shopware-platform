import template from './sw-extension-app-module-error-page.html.twig';
import './sw-extension-app-module-error-page.scss';

/**
 * @sw-package checkout
 * @private
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },

    methods: {
        goBack(): void {
            this.$router.go(-1);
        },
    },
});
