<?php

namespace Tests\Serialize;

use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\PropertyHandler\CamelToSnakeCase;
use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;
use ByJG\Serializer\PropertyHandler\PropertyNameMapper;
use ByJG\Serializer\PropertyHandler\SnakeToCamelCase;
use ByJG\Serializer\Serialize;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Tests\Sample\ModelPropertyPattern;
use Tests\Sample\ModelPublic;
use Tests\Sample\SampleLevel;
use Tests\Sample\SampleStatus;
use Tests\Sample\SampleModel;
use TypeError;

class ObjectCopyTest extends TestCase
{
    public function testCopy_Constructor(): void
    {
        $object1 = new SampleModel( ['Id' => 10, 'Name' => 'Joao']);
        $this->assertEquals(10, $object1->Id);
        $this->assertEquals('Joao', $object1->getName());
    }

    public function testCopy_Array(): void
    {
        $object1 = new SampleModel();
        $object1->copyFrom(['Id' => 10, 'Name' => 'Joao']);
        $this->assertEquals(10, $object1->Id);
        $this->assertEquals('Joao', $object1->getName());
    }

    public function testCopy_StdClass(): void
    {
        $stdClass = new stdClass();
        $stdClass->Id = 10;
        $stdClass->Name = 'Joao';

        $object1 = new SampleModel();
        $object1->copyFrom($stdClass);
        $this->assertEquals(10, $object1->Id);
        $this->assertEquals('Joao', $object1->getName());
    }

    public function testCopyTo_Object(): void
    {
        $object1 = new SampleModel();
        $object1->Id = 10;
        $object1->setName('Joao');

        $object2 = new SampleModel();
        $object1->copyTo($object2);

        $this->assertEquals(10, $object2->Id);
        $this->assertEquals('Joao', $object2->getName());
    }

    public function testCopyTo_stdClass(): void
    {
        $object1 = new SampleModel();
        $object1->Id = 10;
        $object1->setName('Joao');

        $object2 = new stdClass();
        $object1->copyTo($object2);

        $this->assertEquals(10, $object2->Id);
        $this->assertEquals('Joao', $object2->Name);
    }

    public function testToArray(): void
    {
        $object1 = new SampleModel();
        $object1->Id = 10;
        $object1->setName('Joao');

        $object2 = $object1->toArray();

        $this->assertEquals(10, $object2['Id']);
        $this->assertEquals('Joao', $object2['Name']);
    }

    public function testComplexCopy(): void
    {
        $model = new ModelPublic(20, 'JG');

        $data = new stdClass();
        $data->Id = 10;
        $data->Name = $model;

        $object = new SampleModel($data);

        $this->assertEquals(10, $object->Id);
        $this->assertEquals($model, $object->getName());
    }

    public function testCopyToArray(): void
    {
        $object1 = new SampleModel();
        $object1->Id = 10;
        $object1->setName('Joao');

        $array = [];

        ObjectCopy::copy($object1, $array);

        $this->assertEquals(10, $array['Id']);
        $this->assertEquals('Joao', $array['Name']);
    }

    public function testToArrayFrom(): void
    {
        $object1 = new SampleModel();
        $object1->Id = 10;
        $object1->setName('Joao');

        $result = Serialize::from($object1)->toArray();

        $this->assertEquals(
            [
                'Id' => 10,
                'Name' => 'Joao'
            ],
            $result
        );
    }

    public function testToArrayFrom2(): void
    {
        $object1 = new ModelPropertyPattern();
        $object1->setBirthdate('1974-01-26');
        $object1->setClientName('Joao');
        $object1->setIdModel(1);

        $result = Serialize::from($object1)->toArray();

        $this->assertEquals(
            [
                'IdModel' => 1,
                'ClientName' => "Joao",
                'birthdate' => "1974-01-26"
            ],
            $result
        );
    }

    public function testObjectCopy(): void
    {
        $source = [
            "Id" => 1,
            "Name" => "Joao",
            "Ignored" => "Ignored"
        ];

        $target = new ModelPublic(5, "Test");
        ObjectCopy::copy($source, $target, new SnakeToCamelCase());

        $this->assertEquals(1, $target->Id);
        $this->assertEquals('Joao', $target->Name);
    }

    public function testPropertyPatterSnakeToCamel(): void
    {
        $source = new stdClass();
        $source->id_model = 1;
        $source->client_name = 'Joao';
        $source->age = 49;

        $target = new stdClass();
        ObjectCopy::copy($source, $target, new SnakeToCamelCase());

        $this->assertEquals(1, $target->idModel);
        $this->assertEquals('Joao', $target->clientName);
        $this->assertEquals(49, $target->age);
    }

    /**
     * The property handler is called for an anonymous class source, as for a class
     */
    public function testPropertyHandlerWithAnAnonymousClassSource(): void
    {
        $source = new class {
            public string $client_name = 'Joao'; // NOSONAR: snake_case is what SnakeToCamelCase converts
        };

        $target = new stdClass();
        ObjectCopy::copy($source, $target, new SnakeToCamelCase());

        $this->assertEquals((object)['clientName' => 'Joao'], $target);
    }


    public function testPropertyPatterCamelToSnake(): void
    {
        $source = new stdClass();
        $source->idModel = 1;
        $source->clientName = 'Joao';
        $source->age = 49;

        $target = new stdClass();

        ObjectCopy::copy($source, $target, new CamelToSnakeCase());

        $this->assertEquals(1, $target->id_model);
        $this->assertEquals('Joao', $target->client_name);
        $this->assertEquals(49, $target->age);
    }

    public function testPropertyDifferentName(): void
    {
        $source = new stdClass();
        $source->idModel = 1;
        $source->clientName = 'Joao';
        $source->age = 49;

        $target = new stdClass();

        ObjectCopy::copy($source, $target, new PropertyNameMapper(['idModel' => 'x', 'clientName' => 'y']));

        $this->assertEquals(1, $target->x);
        $this->assertEquals('Joao', $target->y);
        $this->assertEquals(49, $target->age);
    }

    public function testPropertyHandlerWithValueTransformation(): void
    {
        $source = new stdClass();
        $source->idModel = 1;
        $source->clientName = 'Joao';
        $source->age = 49;

        $target = new stdClass();

        $valueHandler = function ($propName, $targetName, $value): string {
            return "$propName-$targetName-$value";
        };

        ObjectCopy::copy(
            $source, 
            $target, 
            new PropertyNameMapper(['idModel' => 'x', 'clientName' => 'y'], $valueHandler)
        );

        $this->assertEquals("idModel-x-1", $target->x);
        $this->assertEquals('clientName-y-Joao', $target->y);
        $this->assertEquals("age-age-49", $target->age);
    }

    public function testValueTransformationWithSnakeToCamel(): void
    {
        $source = new stdClass();
        $source->id_model = 1;
        $source->client_name = 'Joao';
        $source->age = 49;

        $target = new stdClass();

        $valueHandler = function ($propName, $targetName, $value) {
            if ($targetName === 'clientName') {
                return strtoupper($value);
            }
            return $value;
        };

        ObjectCopy::copy($source, $target, new SnakeToCamelCase($valueHandler));

        $this->assertEquals(1, $target->idModel);
        $this->assertEquals('JOAO', $target->clientName);
        $this->assertEquals(49, $target->age);
    }

    public static function fastPathProvider(): array
    {
        return [
            'setter and public property' => [
                ['Id' => '10', 'Name' => 'Joao'],
                fn () => new SampleModel(),
            ],
            'stdClass target' => [
                ['Id' => 10, 'Name' => 'Joao'],
                fn () => new stdClass(),
            ],
            'array target' => [
                ['Id' => 10, 'Name' => 'Joao'],
                fn () => [],
            ],
            'case insensitive property match' => [
                ['id' => 10, 'name' => 'Joao'],
                fn () => new ModelPublic(null, null),
            ],
            'null values' => [
                ['Id' => null, 'Name' => null],
                fn () => new ModelPublic(1, 'Joao'),
            ],
            'unknown property is ignored' => [
                ['Id' => 10, 'doesNotExist' => 'x'],
                fn () => new ModelPublic(null, null),
            ],
        ];
    }

    /**
     * The sources of fastPathProvider(), and the values where the paths could differ: a backed enum, an
     * integer key, nested values (kept whole at the first level)
     */
    public static function handlerFastPathProvider(): array
    {
        return self::fastPathProvider() + [
            'backed enum values' => [
                ['status' => SampleStatus::Paid, 'level' => SampleLevel::Low, 'Name' => 'Joao'],
                fn () => new stdClass(),
            ],
            'integer keys' => [
                [0 => 'zero', 1 => 'one'],
                fn () => [],
            ],
            'nested values' => [
                ['Id' => ['a' => 1], 'Name' => new ModelPublic(1, 'x')],
                fn () => new stdClass(),
            ],
        ];
    }

    /**
     * With a property handler, an array source also takes the fast path: the handler must get exactly what the
     * Serialize pipeline gives for each key (the same keys in the same order, the same values, the whole source
     * array), and the target must be the same as with no handler.
     */
    #[DataProvider('handlerFastPathProvider')]
    public function testArrayWithHandlerGivesTheHandlerWhatThePipelineGives(array $source, callable $makeTarget): void
    {
        $pipeline = [];
        Serialize::from($source)
            ->withStopAtFirstLevel()
            ->parseAttributes(function ($attribute, $value, $keyName, $propertyName) use (&$pipeline) {
                $pipeline[] = [(string)$propertyName, $value];
            });

        $handler = new class implements PropertyHandlerInterface {
            public array $calls = [];
            public array $sources = [];

            #[\Override]
            public function mapName(string $property): string
            {
                return $property;
            }

            #[\Override]
            public function transformValue(string $propertyName, string $targetName, mixed $value, mixed $instance = null): mixed
            {
                $this->calls[] = [$propertyName, $value];
                $this->sources[] = $instance;
                return $value;
            }
        };

        $withHandler = $makeTarget();
        ObjectCopy::copy($source, $withHandler, $handler);

        $this->assertEquals($pipeline, $handler->calls);
        $this->assertSame(array_fill(0, count($source), $source), $handler->sources);

        $withoutHandler = $makeTarget();
        ObjectCopy::copy($source, $withoutHandler);
        $this->assertEquals($withoutHandler, $withHandler);
    }

    public function testCopyIntoDifferentClassesWithSameShape(): void
    {
        // ObjectCopy caches property resolution statically, keyed by class name, so that
        // cache must not leak between classes that share a property name. Both classes below
        // accept 'Name', but reach it differently: SampleModel only through setName() (the
        // backing property is protected), ModelPublic only as a public property. Copying the
        // same key into both in one process fails if either resolution is reused for the other.
        $withSetter = new SampleModel();
        ObjectCopy::copy(['Name' => 'Joao'], $withSetter);
        $this->assertEquals('Joao', $withSetter->getName());

        $withoutSetter = new ModelPublic(null, null);
        ObjectCopy::copy(['Name' => 'Joao'], $withoutSetter);
        $this->assertEquals('Joao', $withoutSetter->Name);
    }
}
