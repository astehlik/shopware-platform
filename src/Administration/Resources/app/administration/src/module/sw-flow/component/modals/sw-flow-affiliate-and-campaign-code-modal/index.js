import template from './sw-flow-affiliate-and-campaign-code-modal.html.twig';

const { Component, Mixin, Store } = Shopware;
const { ShopwareError } = Shopware.Classes;
const { mapState } = Component.getComponentHelper();

/**
 * @private
 * @sw-package after-sales
 */
export default {
    template,

    inject: ['flowBuilderService'],

    emits: [
        'process-finish',
        'modal-close',
    ],

    mixins: [
        Mixin.getByName('placeholder'),
        Mixin.getByName('notification'),
    ],

    props: {
        sequence: {
            type: Object,
            required: true,
        },
        action: {
            type: String,
            required: false,
            default: null,
        },
    },

    data() {
        return {
            entityError: null,
            entity: null,
            affiliateCode: {
                value: null,
                upsert: false,
            },
            campaignCode: {
                value: null,
                upsert: false,
            },
        };
    },

    computed: {
        entityOptions() {
            if (!this.triggerEvent) {
                return [];
            }

            const allowedAware = this.triggerEvent.aware ?? [];
            const properties = [];
            // eslint-disable-next-line max-len
            return this.flowBuilderService.getAvailableEntities(this.action, this.triggerActions, allowedAware, properties);
        },

        ...mapState(
            () => Store.get('swFlow'),
            [
                'triggerEvent',
                'triggerActions',
            ],
        ),
    },

    watch: {
        entity(value) {
            if (value && this.entityError) {
                this.entityError = null;
            }
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            const entityName = this.flowBuilderService.getEntityNameByAction(this.action);

            if (this.entityOptions.length) {
                this.entity =
                    this.entityOptions.find((option) => option.value === entityName)?.value || this.entityOptions[0].value;
            }

            if (!this.sequence.config) {
                return;
            }

            this.entity = this.sequence.config.entity;
            this.affiliateCode = { ...this.sequence.config.affiliateCode };
            this.campaignCode = { ...this.sequence.config.campaignCode };
        },

        fieldError(field) {
            if (!field || !field.length) {
                return new ShopwareError({
                    code: 'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
                });
            }

            return null;
        },

        onSave() {
            this.entityError = this.fieldError(this.entity);
            if (this.entityError) {
                return;
            }

            const config = {
                entity: this.entity,
                affiliateCode: this.affiliateCode,
                campaignCode: this.campaignCode,
            };

            const sequence = {
                ...this.sequence,
                config,
            };

            this.$emit('process-finish', sequence);
            this.onClose();
        },

        onClose() {
            this.$emit('modal-close');
        },
    },
};
