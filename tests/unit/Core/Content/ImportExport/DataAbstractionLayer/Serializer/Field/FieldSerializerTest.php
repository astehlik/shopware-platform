<?php declare(strict_types=1);

namespace Shopware\Tests\Unit\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Field;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Document\DocumentCollection;
use Shopware\Core\Checkout\Document\DocumentDefinition;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity\EntitySerializer;
use Shopware\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Field\FieldSerializer;
use Shopware\Core\Content\ImportExport\DataAbstractionLayer\Serializer\SerializerRegistry;
use Shopware\Core\Content\ImportExport\ImportExportException;
use Shopware\Core\Content\ImportExport\Processing\Mapping\Mapping;
use Shopware\Core\Content\ImportExport\Struct\Config;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BlobField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(FieldSerializer::class)]
class FieldSerializerTest extends TestCase
{
    /**
     * @throws \JsonException
     */
    #[DataProvider('serializeDataProvider')]
    public function testSerialize(Field $field, mixed $inputValue, mixed $expected): void
    {
        $fieldSerializer = new FieldSerializer();
        $config = new Config([], [], []);

        static::assertSame($expected, $this->first($fieldSerializer->serialize($config, $field, $inputValue)));
    }

    /**
     * @throws \JsonException
     */
    #[DataProvider('deserializeDataProvider')]
    public function testDeserialize(Field $field, mixed $inputValue, mixed $expected): void
    {
        $fieldSerializer = new FieldSerializer();
        $config = new Config([], [], []);

        static::assertSame($expected, $fieldSerializer->deserialize($config, $field, $inputValue));
    }

    public function testOrderDeliverySerialize(): void
    {
        $fieldSerializer = new FieldSerializer();
        $registry = new SerializerRegistry([new EntitySerializer()], [new FieldSerializer()]);
        $fieldSerializer->setRegistry($registry);

        $config = new Config([], [], []);

        $field = new OneToManyAssociationField('deliveries', OrderDeliveryDefinition::class, 'order_id');

        $definitionRegistry = $this->createMock(DefinitionInstanceRegistry::class);

        $orderDeliveryDefinition = new OrderDeliveryDefinition();
        $orderDeliveryDefinition->compile($definitionRegistry);

        $definitionRegistry->method('getByClassOrEntityName')->willReturn($orderDeliveryDefinition);

        $field->compile($definitionRegistry);

        $deliveryId = Uuid::randomHex();
        $deliveries = new OrderDeliveryCollection([(new OrderDeliveryEntity())->assign(['id' => $deliveryId])]);

        static::assertSame([
            '_uniqueIdentifier' => $deliveryId,
            'versionId' => null,
            'translated' => [],
            'customFields' => null,
            'id' => $deliveryId,
        ], $this->first($fieldSerializer->serialize($config, $field, $deliveries)));
    }

    public function testEmptyOrderDeliverySerialize(): void
    {
        $fieldSerializer = new FieldSerializer();
        $registry = new SerializerRegistry([new EntitySerializer()], [new FieldSerializer()]);
        $fieldSerializer->setRegistry($registry);

        $config = new Config([], [], []);

        $field = new OneToManyAssociationField('deliveries', OrderDeliveryDefinition::class, 'order_id');

        $definitionRegistry = $this->createMock(DefinitionInstanceRegistry::class);

        $orderDeliveryDefinition = new OrderDeliveryDefinition();
        $orderDeliveryDefinition->compile($definitionRegistry);

        $definitionRegistry->method('getByClassOrEntityName')->willReturn($orderDeliveryDefinition);

        $field->compile($definitionRegistry);

        static::assertNull($this->first($fieldSerializer->serialize($config, $field, null)));

        static::assertSame([], $this->first($fieldSerializer->serialize($config, $field, new OrderDeliveryCollection())));
    }

    public function testOrderTransactionsSerialize(): void
    {
        $fieldSerializer = new FieldSerializer();
        $registry = new SerializerRegistry([new EntitySerializer()], [new FieldSerializer()]);
        $fieldSerializer->setRegistry($registry);

        $config = new Config([], [], []);

        $field = new OneToManyAssociationField('transactions', OrderTransactionDefinition::class, 'order_id');

        $definitionRegistry = $this->createMock(DefinitionInstanceRegistry::class);

        $orderTransactionDefinition = new OrderTransactionDefinition();
        $orderTransactionDefinition->compile($definitionRegistry);

        $definitionRegistry->method('getByClassOrEntityName')->willReturn($orderTransactionDefinition);

        $field->compile($definitionRegistry);

        $transactionId = Uuid::randomHex();
        $transactions = new OrderTransactionCollection([(new OrderTransactionEntity())->assign(['id' => $transactionId])]);

        static::assertSame([
            '_uniqueIdentifier' => $transactionId,
            'versionId' => null,
            'translated' => [],
            'validationData' => '[]',
            'customFields' => null,
            'id' => $transactionId,
        ], $this->first($fieldSerializer->serialize($config, $field, $transactions)));
    }

    public function testEmptyOrderTransactionsSerialize(): void
    {
        $fieldSerializer = new FieldSerializer();
        $registry = new SerializerRegistry([new EntitySerializer()], [new FieldSerializer()]);
        $fieldSerializer->setRegistry($registry);

        $config = new Config([], [], []);

        $field = new OneToManyAssociationField('transactions', OrderTransactionDefinition::class, 'order_id');

        $definitionRegistry = $this->createMock(DefinitionInstanceRegistry::class);

        $orderTransactionDefinition = new OrderTransactionDefinition();
        $orderTransactionDefinition->compile($definitionRegistry);

        $definitionRegistry->method('getByClassOrEntityName')->willReturn($orderTransactionDefinition);

        $field->compile($definitionRegistry);

        static::assertNull($this->first($fieldSerializer->serialize($config, $field, null)));

        static::assertSame([], $this->first($fieldSerializer->serialize($config, $field, new OrderTransactionCollection())));
    }

    public function testUnhandledOneToManyAssociationField(): void
    {
        $fieldSerializer = new FieldSerializer();
        $registry = new SerializerRegistry([new EntitySerializer()], [new FieldSerializer()]);
        $fieldSerializer->setRegistry($registry);

        $config = new Config([], [], []);

        $field = new OneToManyAssociationField('documents', DocumentDefinition::class, 'order_id');

        $definitionRegistry = $this->createMock(DefinitionInstanceRegistry::class);

        $orderDeliveryDefinition = new OrderDeliveryDefinition();
        $orderDeliveryDefinition->compile($definitionRegistry);

        $definitionRegistry->method('getByClassOrEntityName')->willReturn($orderDeliveryDefinition);

        $field->compile($definitionRegistry);

        $documents = new DocumentCollection([(new DocumentEntity())->assign(['id' => Uuid::randomHex()])]);

        static::assertNull($this->first($fieldSerializer->serialize($config, $field, $documents)));
    }

    /**
     * @return iterable<string, array{field: Field, inputValue: mixed, expected: mixed}>
     */
    public static function serializeDataProvider(): iterable
    {
        yield 'int field #1' => [
            'field' => new IntField('foo', 'foo'),
            'inputValue' => '',
            'expected' => '',
        ];

        yield 'int field #2' => [
            'field' => new IntField('foo', 'foo'),
            'inputValue' => 0,
            'expected' => '0',
        ];

        yield 'int field #3' => [
            'field' => new IntField('foo', 'foo'),
            'inputValue' => 3123412344321,
            'expected' => '3123412344321',
        ];

        yield 'bool field with true value' => [
            'field' => new BoolField('foo', 'foo'),
            'inputValue' => true,
            'expected' => '1',
        ];

        $inheritedBoolField = new BoolField('foo', 'foo');
        $inheritedBoolField->addFlags(new Inherited());
        yield 'bool field with null value and inherited flag' => [
            'field' => $inheritedBoolField,
            'inputValue' => null,
            'expected' => null,
        ];

        yield 'bool field with null value and no inherited flag' => [
            'field' => new BoolField('foo', 'foo'),
            'inputValue' => null,
            'expected' => '0',
        ];

        yield 'bool field with false value' => [
            'field' => new BoolField('foo', 'foo'),
            'inputValue' => false,
            'expected' => '0',
        ];

        yield 'json field' => [
            'field' => new JsonField('foo', 'foo'),
            'inputValue' => ['foo' => 'baz'],
            'expected' => '{"foo":"baz"}',
        ];

        yield 'blob field #1: string' => [
            'field' => new BlobField('foo', 'foo'),
            'inputValue' => 'plain string',
            'expected' => 'plain string',
        ];

        yield 'blob field #2: float' => [
            'field' => new BlobField('foo', 'foo'),
            'inputValue' => 123.23,
            'expected' => '123.23',
        ];

        yield 'blob field #3: null' => [
            'field' => new BlobField('foo', 'foo'),
            'inputValue' => null,
            'expected' => null,
        ];

        yield 'blob field #4: bool' => [
            'field' => new BlobField('foo', 'foo'),
            'inputValue' => true,
            'expected' => '1',
        ];

        yield 'blob field #5: array' => [
            'field' => new BlobField('foo', 'foo'),
            'inputValue' => ['foo' => 'baz'],
            'expected' => '{"foo":"baz"}',
        ];

        yield 'blob field #6: struct' => [
            'field' => new BlobField('foo', 'foo'),
            'inputValue' => new ArrayStruct(['foo' => 'baz']),
            'expected' => '{"extensions":[],"apiAlias":null,"foo":"baz"}',
        ];

        yield 'blob field #7: rule struct' => [
            'field' => new BlobField('foo', 'foo'),
            'inputValue' => new AndRule(),
            'expected' => '{"_name":"andContainer","rules":[]}',
        ];

        yield 'blob field #8: Stringable' => [
            'field' => new BlobField('foo', 'foo'),
            'inputValue' => new DummyStringable(),
            'expected' => 'dummy',
        ];
    }

    /**
     * @return iterable<string, array{field: Field, inputValue: mixed, expected: mixed}>
     */
    public static function deserializeDataProvider(): iterable
    {
        yield 'int field #1' => [
            'field' => new IntField('foo', 'foo'),
            'inputValue' => null,
            'expected' => null,
        ];

        yield 'int field #2' => [
            'field' => new IntField('foo', 'foo'),
            'inputValue' => '0',
            'expected' => 0,
        ];

        yield 'int field #3' => [
            'field' => new IntField('foo', 'foo'),
            'inputValue' => '3123412344321',
            'expected' => 3123412344321,
        ];

        yield 'bool field' => [
            'field' => new BoolField('foo', 'foo'),
            'inputValue' => '1',
            'expected' => true,
        ];

        yield 'json field' => [
            'field' => new JsonField('foo', 'foo'),
            'inputValue' => '{"foo":"baz"}',
            'expected' => ['foo' => 'baz'],
        ];

        yield 'blob field #1: string' => [
            'field' => new BlobField('foo', 'foo'),
            'inputValue' => 'plain string',
            'expected' => 'plain string',
        ];

        yield 'blob field #2: float' => [
            'field' => new BlobField('foo', 'foo'),
            'inputValue' => '123.23',
            'expected' => '123.23',
        ];

        yield 'blob field #3: null' => [
            'field' => new BlobField('foo', 'foo'),
            'inputValue' => null,
            'expected' => null,
        ];

        yield 'blob field #4: bool' => [
            'field' => new BlobField('foo', 'foo'),
            'inputValue' => '1',
            'expected' => '1',
        ];

        yield 'blob field #5: array' => [
            'field' => new BlobField('foo', 'foo'),
            'inputValue' => '{"foo":"baz"}',
            'expected' => '{"foo":"baz"}',
        ];

        yield 'blob field #6: struct' => [
            'field' => new BlobField('foo', 'foo'),
            'inputValue' => '{"extensions":[],"apiAlias":null,"foo":"baz"}',
            'expected' => '{"extensions":[],"apiAlias":null,"foo":"baz"}',
        ];

        yield 'blob field #7: rule struct' => [
            'field' => new BlobField('foo', 'foo'),
            'inputValue' => '{"_name":"andContainer","rules":[]}',
            'expected' => '{"_name":"andContainer","rules":[]}',
        ];

        yield 'blob field #8: Stringable' => [
            'field' => new BlobField('foo', 'foo'),
            'inputValue' => 'dummy',
            'expected' => 'dummy',
        ];
    }

    #[DataProvider('deserializeShouldThrowExceptionTestDataProvider')]
    public function testDeserializeShouldThrowException(Field $field, ?string $value, string $expectedMessage): void
    {
        $fieldSerializer = new FieldSerializer();
        $config = new Config([new Mapping('bar')], [], []);

        $this->expectException(ImportExportException::class);
        $this->expectExceptionMessage($expectedMessage);

        $fieldSerializer->deserialize($config, $field, $value);
    }

    /**
     * @return iterable<string, array{field: Field, value: string|null, expectedMessage: string}>
     */
    public static function deserializeShouldThrowExceptionTestDataProvider(): iterable
    {
        yield 'int field with not parsable value' => [
            'field' => new IntField('foo', 'bar'),
            'value' => 'foo1Bar',
            'expectedMessage' => 'Deserialization failed for field "bar" with value "foo1Bar" to type "integer"',
        ];

        yield 'date field with not parsable value' => [
            'field' => new DateField('foo', 'bar'),
            'value' => '45-13-22517',
            'expectedMessage' => 'Deserialization failed for field "bar" with value "45-13-22517" to type "date"',
        ];

        yield 'bool field with not parsable value' => [
            'field' => new BoolField('foo', 'bar'),
            'value' => 'of course',
            'expectedMessage' => 'Deserialization failed for field "bar" with value "of course" to type "boolean"',
        ];

        yield 'json field with not parsable value' => [
            'field' => new JsonField('foo', 'bar'),
            'value' => '{FooBar',
            'expectedMessage' => 'Deserialization failed for field "bar" with value "{FooBar" to type "json"',
        ];

        yield 'id field with not parsable value' => [
            'field' => new IdField('foo', 'bar'),
            'value' => '1ab98a64fcb64d|2cb08321a122deacc1',
            'expectedMessage' => 'Deserialization failed for field "bar" with value "1ab98a64fcb64d|2cb08321a122deacc1" to type "uuid"',
        ];
    }

    /**
     * @param iterable<mixed>|null $iterable
     */
    private function first(?iterable $iterable): mixed
    {
        if ($iterable === null) {
            return null;
        }

        foreach ($iterable as $value) {
            return $value;
        }

        return null;
    }
}

/**
 * @internal
 */
class DummyStringable implements \Stringable
{
    public function __toString(): string
    {
        return 'dummy';
    }
}
