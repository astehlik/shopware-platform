<?php declare(strict_types=1);

namespace Shopware\Core\System\Salutation\SalesChannel;

use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\StoreApiResponse;
use Shopware\Core\System\Salutation\SalutationCollection;

/**
 * @extends StoreApiResponse<EntitySearchResult<SalutationCollection>>
 */
#[Package('checkout')]
class SalutationRouteResponse extends StoreApiResponse
{
    public function getSalutations(): SalutationCollection
    {
        return $this->object->getEntities();
    }
}
