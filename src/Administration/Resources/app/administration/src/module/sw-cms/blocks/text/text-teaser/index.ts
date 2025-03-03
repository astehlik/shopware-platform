/**
 * @private
 * @sw-package discovery
 */
Shopware.Component.register('sw-cms-preview-text-teaser', () => import('./preview'));
/**
 * @private
 * @sw-package discovery
 */
Shopware.Component.register('sw-cms-block-text-teaser', () => import('./component'));

/**
 * @private
 * @sw-package discovery
 */
Shopware.Service('cmsService').registerCmsBlock({
    name: 'text-teaser',
    label: 'sw-cms.blocks.text.textTeaser.label',
    category: 'text',
    component: 'sw-cms-block-text-teaser',
    previewComponent: 'sw-cms-preview-text-teaser',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: null,
        marginRight: null,
        sizingMode: 'boxed',
    },
    slots: {
        content: {
            type: 'text',
            default: {
                config: {
                    content: {
                        source: 'static',
                        value: `
                        <h2 style="text-align: center;">Lorem Ipsum dolor sit amet</h2>
                        <p style="text-align: center;"><i>Lorem ipsum dolor sit amet, consetetur sadipscing elitr,
                        sed diam nonumy eirmod tempor invidunt ut labore</i></p>
                        `.trim(),
                    },
                },
            },
        },
    },
});
