<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Document\Renderer;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;

#[Package('after-sales')]
final class OrderDocumentCriteriaFactory
{
    /**
     * @internal
     */
    private function __construct()
    {
    }

    /**
     * @param array<int, string> $ids
     */
    public static function create(array $ids, string $deepLinkCode = '', ?string $documentType = null): Criteria
    {
        $criteria = new Criteria($ids);

        $criteria->addAssociations([
            'lineItems',
            'transactions.paymentMethod',
            'currency',
            'language.locale',
            'addresses.country',
            'addresses.salutation',
            'addresses.countryState',
            'deliveries.positions',
            'deliveries.shippingMethod',
            'deliveries.shippingOrderAddress.country',
            'deliveries.shippingOrderAddress.countryState',
            'orderCustomer.customer',
            'orderCustomer.salutation',
        ]);

        $criteria->getAssociation('lineItems')->addSorting(new FieldSorting('position'));
        $criteria->getAssociation('transactions')->addSorting(new FieldSorting('createdAt'));
        $criteria->getAssociation('deliveries')->addSorting(new FieldSorting('createdAt'));

        if ($documentType) {
            $criteria->addAssociation('documents.documentType');
            $criteria->getAssociation('documents')
                ->addFilter(new EqualsFilter('documentType.technicalName', $documentType))
                ->setLimit(1);
        }

        if ($deepLinkCode !== '') {
            $criteria->addFilter(new EqualsFilter('deepLinkCode', $deepLinkCode));
        }

        return $criteria;
    }
}
