import type CriteriaType from 'src/core/data/criteria.data';
import type Repository from 'src/core/data/repository.data';
import type { PaymentOverviewCard } from '../../store/overview-cards.store';
import template from './sw-settings-payment-overview.html.twig';
import './sw-settings-payment-overview.scss';

/**
 * @sw-package checkout
 */

interface PaymentMethodCard {
    id: string;
    hasCustomCard: boolean;
    component?: string;
    positionId: string;
    position: number;
    paymentMethod?: Entity<'payment_method'>;
    paymentMethods?: EntityCollection<'payment_method'>;
}

const { Mixin } = Shopware;
const { Criteria } = Shopware.Data;
const { cloneDeep } = Shopware.Utils.object;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'repositoryFactory',
        'acl',
    ],

    mixins: [
        Mixin.getByName('notification'),
    ],

    data(): {
        isLoading: boolean;
        showSortingModal: boolean;
        paymentMethods: EntityCollection<'payment_method'> | [];
    } {
        return {
            paymentMethods: [],
            isLoading: false,
            showSortingModal: false,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        customCards(): PaymentOverviewCard[] {
            return Shopware.Store.get('paymentOverviewCard').cards ?? [];
        },

        paymentMethodRepository(): Repository<'payment_method'> {
            return this.repositoryFactory.create('payment_method');
        },

        paymentMethodCriteria(): CriteriaType {
            const criteria = new Criteria(1, 500);

            criteria.addAssociation('media');
            criteria.addSorting(Criteria.sort('position', 'ASC'));

            return criteria;
        },

        isEmpty(): boolean {
            return !this.isLoading && this.paymentMethods.length === 0;
        },

        paymentMethodCards(): PaymentMethodCard[] {
            if (this.paymentMethods.length === 0) {
                return [];
            }

            const paymentMethodCards = [];
            let paymentMethods = cloneDeep(this.paymentMethods);

            this.customCards.forEach((customCard: PaymentOverviewCard) => {
                const customPaymentMethods = paymentMethods
                    // @ts-expect-error - can be undefined
                    .filter((pm) => customCard.paymentMethodHandlers.includes(pm.formattedHandlerIdentifier));

                if (customPaymentMethods.length === 0) {
                    return;
                }

                paymentMethodCards.push(<PaymentMethodCard>{
                    id: customPaymentMethods[0].id,
                    hasCustomCard: true,
                    component: customCard.component,
                    // @ts-expect-error - can be undefined
                    position: Math.min(...customPaymentMethods.map((pm) => pm.position)),
                    positionId: customCard.positionId,
                    paymentMethods: customPaymentMethods,
                });

                // @ts-expect-error - can be undefined
                paymentMethods = paymentMethods
                    // @ts-expect-error - can be undefined
                    .filter((pm) => !customCard.paymentMethodHandlers.includes(pm.formattedHandlerIdentifier));
            });

            paymentMethodCards.push(
                ...paymentMethods.map(
                    (paymentMethod) =>
                        <PaymentMethodCard>{
                            id: paymentMethod.id,
                            hasCustomCard: false,
                            paymentMethod,
                            position: paymentMethod.position,
                            positionId: '',
                        },
                ),
            );

            return paymentMethodCards.sort((a: PaymentMethodCard, b: PaymentMethodCard) => {
                return a.position - b.position;
            });
        },
    },

    created(): void {
        this.createdComponent();
    },

    methods: {
        createdComponent(): void {
            this.loadPaymentMethods();
        },

        loadPaymentMethods(): void {
            this.isLoading = true;

            void this.paymentMethodRepository
                .search(this.paymentMethodCriteria)
                .then((items) => {
                    this.paymentMethods = items;
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },

        onChangeLanguage(languageId: string): void {
            Shopware.Store.get('context').api.languageId = languageId;
            this.loadPaymentMethods();
        },

        togglePaymentMethodActive(paymentMethod: Entity<'payment_method'>): void {
            const paymentMethodEntity = this.paymentMethods.find((pm) => pm.id === paymentMethod.id);

            if (!paymentMethodEntity) {
                return;
            }

            paymentMethodEntity.active = paymentMethod.active;

            this.paymentMethodRepository
                .save(paymentMethodEntity)
                .then(() => {
                    this.loadPaymentMethods();
                    this.showActivationSuccessNotification(
                        paymentMethodEntity.translated?.name ?? '',
                        // @ts-expect-error - can be undefined
                        paymentMethodEntity.active,
                    );
                })
                .catch(() => {
                    this.showActivationErrorNotification(
                        paymentMethodEntity.translated?.name ?? '',
                        // @ts-expect-error - can be undefined
                        paymentMethodEntity.active,
                    );
                    void this.$nextTick(() => {
                        paymentMethodEntity.active = !paymentMethodEntity.active;
                    });
                });
        },

        showActivationSuccessNotification(name: string, active: boolean) {
            const message = active
                ? this.$t('sw-settings-payment.overview.notification.activationSuccess', { name }, 0)
                : this.$t('sw-settings-payment.overview.notification.deactivationSuccess', { name }, 0);

            this.createNotificationSuccess({ message });
        },

        showActivationErrorNotification(name: string, active: boolean) {
            const message = active
                ? this.$t('sw-settings-payment.overview.notification.activationError', { name }, 0)
                : this.$t('sw-settings-payment.overview.notification.deactivationError', { name }, 0);

            this.createNotificationError({ message });
        },
    },
});
