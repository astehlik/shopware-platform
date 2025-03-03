<?php declare(strict_types=1);

namespace Shopware\Core\Framework\DataAbstractionLayer\Attribute;

use Shopware\Core\Framework\Log\Package;

#[Package('framework')]
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class ReferenceVersion extends Field
{
    public const TYPE = 'reference-version';

    public function __construct(public string $entity, public ?string $column = null)
    {
        parent::__construct(type: self::TYPE, api: true, column: $column);
    }
}
