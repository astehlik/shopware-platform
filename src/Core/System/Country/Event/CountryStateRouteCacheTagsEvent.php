<?php declare(strict_types=1);

namespace Shopware\Core\System\Country\Event;

use Shopware\Core\Framework\Adapter\Cache\StoreApiRouteCacheTagsEvent;
use Shopware\Core\Framework\Log\Package;

#[Package('fundamentals@discovery')]
class CountryStateRouteCacheTagsEvent extends StoreApiRouteCacheTagsEvent
{
}
