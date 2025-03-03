<?php declare(strict_types=1);

namespace Shopware\Core\Framework\DataAbstractionLayer\Field;

use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\CronIntervalFieldSerializer;
use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class CronIntervalField extends Field implements StorageAware
{
    public function __construct(
        private string $storageName,
        string $propertyName
    ) {
        parent::__construct($propertyName);
    }

    public function getStorageName(): string
    {
        return $this->storageName;
    }

    protected function getSerializerClass(): string
    {
        return CronIntervalFieldSerializer::class;
    }
}
