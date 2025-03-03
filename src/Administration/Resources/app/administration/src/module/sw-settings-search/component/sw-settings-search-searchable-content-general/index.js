/**
 * @sw-package inventory
 */
import template from './sw-settings-search-searchable-content-general.html.twig';

const { Mixin } = Shopware;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    inject: [
        'acl',
    ],

    emits: [
        'data-load',
        'config-save',
    ],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification'),
    ],

    props: {
        isEmpty: {
            type: Boolean,
            required: true,
        },

        columns: {
            type: Array,
            required: true,
        },

        repository: {
            type: Object,
            required: true,
        },

        searchConfigs: {
            type: Array,
            required: false,
            default() {
                return null;
            },
        },

        fieldConfigs: {
            type: Array,
            required: true,
        },

        isLoading: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },

    methods: {
        getList() {
            // Empty method needed to avoid warning from listing mixin
        },

        getMatchingFields(fieldName) {
            if (!fieldName) {
                return '';
            }

            const fieldItem = this.fieldConfigs.find((fieldConfig) => fieldConfig.value === fieldName);

            return fieldItem ? fieldItem.label : '';
        },

        onSelectField(currentField) {
            const { defaultConfigs } = this.fieldConfigs.find((option) => option.value === currentField.field);
            this.searchConfigs.forEach((configItem) => {
                if (configItem._isNew) {
                    configItem.ranking = defaultConfigs.ranking;
                    configItem.searchable = defaultConfigs.searchable;
                    configItem.tokenize = defaultConfigs.tokenize;
                }

                return configItem;
            });
        },

        onInlineEditSave(promise) {
            promise
                .then(() => {
                    this.createNotificationSuccess({
                        message: this.$tc('sw-settings-search.notification.saveSuccess'),
                    });
                })
                .catch(() => {
                    this.createNotificationError({
                        message: this.$tc('sw-settings-search.notification.saveError'),
                    });
                })
                .finally(() => {
                    this.$emit('data-load');
                });
        },

        onInlineEditCancel() {
            this.$emit('data-load');
        },

        onInlineEditItem(item) {
            this.$refs.swSettingsSearchableContentGrid.onDbClickCell(item);
        },

        onResetRanking(currentField) {
            if (!currentField.field) {
                this.createNotificationError({
                    message: this.$tc('sw-settings-search.notification.saveError'),
                });

                this.$emit('data-load');
                return;
            }

            const currentItem = this.searchConfigs.find((item) => item.field === currentField.field);
            if (!currentItem) {
                this.createNotificationError({
                    message: this.$tc('sw-settings-search.notification.saveError'),
                });

                return;
            }
            currentItem.ranking = this.getConfigRankingDefault(currentField.field);

            this.$emit('config-save');
        },

        getConfigRankingDefault(fieldName) {
            if (!fieldName) {
                return 0;
            }

            const fieldConfigDefault = this.fieldConfigs.find((fieldConfig) => fieldConfig.value === fieldName);

            return fieldConfigDefault ? fieldConfigDefault.defaultConfigs.ranking : 0;
        },
    },
};
