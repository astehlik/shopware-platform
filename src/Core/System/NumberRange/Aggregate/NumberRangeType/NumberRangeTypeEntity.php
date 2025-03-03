<?php declare(strict_types=1);

namespace Shopware\Core\System\NumberRange\Aggregate\NumberRangeType;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\NumberRange\Aggregate\NumberRangeSalesChannel\NumberRangeSalesChannelEntity;
use Shopware\Core\System\NumberRange\Aggregate\NumberRangeTypeTranslation\NumberRangeTypeTranslationCollection;
use Shopware\Core\System\NumberRange\NumberRangeCollection;

#[Package('framework')]
class NumberRangeTypeEntity extends Entity
{
    use EntityCustomFieldsTrait;
    use EntityIdTrait;

    protected string $typeName;

    protected string $technicalName;

    protected bool $global;

    protected ?NumberRangeCollection $numberRanges = null;

    protected ?NumberRangeSalesChannelEntity $numberRangeSalesChannels = null;

    protected ?NumberRangeTypeTranslationCollection $translations = null;

    public function getTypeName(): string
    {
        return $this->typeName;
    }

    public function setTypeName(string $typeName): void
    {
        $this->typeName = $typeName;
    }

    public function getGlobal(): bool
    {
        return $this->global;
    }

    public function setGlobal(bool $global): void
    {
        $this->global = $global;
    }

    public function getNumberRanges(): ?NumberRangeCollection
    {
        return $this->numberRanges;
    }

    public function setNumberRanges(NumberRangeCollection $numberRanges): void
    {
        $this->numberRanges = $numberRanges;
    }

    public function getTranslations(): ?NumberRangeTypeTranslationCollection
    {
        return $this->translations;
    }

    public function setTranslations(NumberRangeTypeTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

    public function getTechnicalName(): string
    {
        return $this->technicalName;
    }

    public function setTechnicalName(string $technicalName): void
    {
        $this->technicalName = $technicalName;
    }

    public function getNumberRangeSalesChannels(): ?NumberRangeSalesChannelEntity
    {
        return $this->numberRangeSalesChannels;
    }

    public function setNumberRangeSalesChannels(NumberRangeSalesChannelEntity $numberRangeSalesChannels): void
    {
        $this->numberRangeSalesChannels = $numberRangeSalesChannels;
    }
}
