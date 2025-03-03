<?php declare(strict_types=1);

namespace Shopware\Elasticsearch\Framework\DataAbstractionLayer\Event;

use OpenSearchDSL\Search;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Event\ShopwareEvent;
use Shopware\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @codeCoverageIgnore
 */
#[Package('framework')]
class ElasticsearchEntityAggregatorSearchedEvent extends Event implements ShopwareEvent
{
    public function __construct(
        public readonly AggregationResultCollection $result,
        public readonly Search $search,
        public readonly EntityDefinition $definition,
        public readonly Criteria $criteria,
        private readonly Context $context
    ) {
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
