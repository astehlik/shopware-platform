<?php declare(strict_types=1);

namespace Shopware\Tests\Integration\Core\Content\ImportExport\Processing\Mapping;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\ImportExport\Processing\Mapping\CriteriaBuilder;
use Shopware\Core\Content\ImportExport\Struct\Config;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class CriteriaBuilderTest extends TestCase
{
    use KernelTestBehaviour;

    public function testNoAssociations(): void
    {
        $criteriaBuild = new CriteriaBuilder(static::getContainer()->get(ProductDefinition::class));

        $criteria = new Criteria();
        $config = new Config(
            [
                'name',
            ],
            [],
            []
        );
        $criteriaBuild->enrichCriteria($config, $criteria);

        static::assertEmpty($criteria->getAssociations());
    }

    public function testAssociations(): void
    {
        $criteriaBuild = new CriteriaBuilder(static::getContainer()->get(ProductDefinition::class));

        $criteria = new Criteria();
        $config = new Config(
            [
                'name',
                'translations.name',
                'visibilities.search',
                'manufacturer.media.translations.title',
            ],
            [],
            []
        );
        $criteriaBuild->enrichCriteria($config, $criteria);

        $associations = $criteria->getAssociations();
        static::assertNotEmpty($associations);

        static::assertArrayHasKey('translations', $associations);
        static::assertArrayHasKey('visibilities', $associations);

        static::assertArrayHasKey('manufacturer', $associations);
        $manufacturerAssociations = $associations['manufacturer']->getAssociations();
        static::assertArrayHasKey('media', $manufacturerAssociations);

        static::assertInstanceOf(Criteria::class, $manufacturerAssociations['media']);
        $manufacturerMediaAssociations = $manufacturerAssociations['media']->getAssociations();
        static::assertArrayHasKey('translations', $manufacturerMediaAssociations);
        static::assertInstanceOf(Criteria::class, $manufacturerMediaAssociations['translations']);
    }
}
