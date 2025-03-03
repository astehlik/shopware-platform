<?php declare(strict_types=1);

namespace Shopware\Core\Content\Cms\Aggregate\CmsSlot;

use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSlotTranslation\CmsSlotTranslationEntity;
use Shopware\Core\Content\Cms\CmsException;
use Shopware\Core\Content\Cms\DataResolver\FieldConfig;
use Shopware\Core\Content\Cms\DataResolver\FieldConfigCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('discovery')]
class CmsSlotEntity extends Entity
{
    use EntityCustomFieldsTrait;
    use EntityIdTrait;

    protected string $type;

    protected string $slot;

    protected ?CmsBlockEntity $block = null;

    protected string $blockId;

    /**
     * @var array<mixed>|null
     */
    protected ?array $config = null;

    protected ?FieldConfigCollection $fieldConfig = null;

    /**
     * @var EntityCollection<CmsSlotTranslationEntity>|null
     */
    protected ?EntityCollection $translations = null;

    protected ?Struct $data = null;

    protected bool $locked;

    protected ?string $cmsBlockVersionId = null;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getSlot(): string
    {
        return $this->slot;
    }

    public function setSlot(string $slot): void
    {
        $this->slot = $slot;
    }

    public function getBlock(): ?CmsBlockEntity
    {
        return $this->block;
    }

    public function setBlock(CmsBlockEntity $block): void
    {
        $this->block = $block;
    }

    public function getBlockId(): string
    {
        return $this->blockId;
    }

    public function setBlockId(string $blockId): void
    {
        $this->blockId = $blockId;
    }

    /**
     * @return array<mixed>|null
     */
    public function getConfig(): ?array
    {
        return $this->config;
    }

    /**
     * @param array<mixed> $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
        $this->fieldConfig = null;
    }

    /**
     * @return EntityCollection<CmsSlotTranslationEntity>|null
     */
    public function getTranslations(): ?EntityCollection
    {
        return $this->translations;
    }

    /**
     * @param EntityCollection<CmsSlotTranslationEntity> $translations
     */
    public function setTranslations(EntityCollection $translations): void
    {
        $this->translations = $translations;
    }

    public function getData(): ?Struct
    {
        return $this->data;
    }

    public function setData(Struct $data): void
    {
        $this->data = $data;
    }

    public function getLocked(): bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): void
    {
        $this->locked = $locked;
    }

    public function getFieldConfig(): FieldConfigCollection
    {
        if ($this->fieldConfig) {
            return $this->fieldConfig;
        }

        $collection = new FieldConfigCollection();
        $config = $this->getTranslation('config') ?? [];

        foreach ($config as $key => $data) {
            $source = $data['source'] ?? null;
            $value = $data['value'] ?? null;

            if (!\is_string($source)) {
                throw CmsException::invalidFieldConfigSource($key);
            }

            $collection->add(
                new FieldConfig($key, $source, $value)
            );
        }

        return $this->fieldConfig = $collection;
    }

    public function setFieldConfig(FieldConfigCollection $fieldConfig): void
    {
        $this->fieldConfig = $fieldConfig;
    }

    public function getCmsBlockVersionId(): ?string
    {
        return $this->cmsBlockVersionId;
    }

    public function setCmsBlockVersionId(?string $cmsBlockVersionId): void
    {
        $this->cmsBlockVersionId = $cmsBlockVersionId;
    }
}
