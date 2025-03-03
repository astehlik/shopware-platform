<?php declare(strict_types=1);

namespace Shopware\Core\System\CustomEntity\Schema;

use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal Used for custom entities
 *
 * @phpstan-import-type CustomEntityField from CustomEntitySchemaUpdater
 */
#[Package('framework')]
class DynamicTranslationEntityDefinition extends EntityTranslationDefinition
{
    protected string $root;

    /**
     * @var list<CustomEntityField>
     */
    protected array $fieldDefinitions;

    protected ContainerInterface $container;

    /**
     * @param list<CustomEntityField> $fields
     */
    public static function create(string $root, array $fields, ContainerInterface $container): DynamicTranslationEntityDefinition
    {
        $self = new self();
        $self->root = $root;
        $self->fieldDefinitions = $fields;
        $self->container = $container;

        return $self;
    }

    public function getEntityName(): string
    {
        return $this->root . '_translation';
    }

    protected function getParentDefinitionClass(): string
    {
        return $this->root;
    }

    protected function defineFields(): FieldCollection
    {
        return DynamicFieldFactory::create($this->container, $this->getEntityName(), $this->fieldDefinitions);
    }
}
