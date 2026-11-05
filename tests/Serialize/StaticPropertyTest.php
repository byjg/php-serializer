<?php

namespace Tests\Serialize;

use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\Serialize;
use PHPUnit\Framework\TestCase;
use Tests\Sample\ModelWithStatic;

/**
 * A static property is class state, not data of the object: it is not serialized, and a key with its name is not
 * copied into it (nor as a dynamic property of the object)
 */
class StaticPropertyTest extends TestCase
{
    public function testStaticPropertiesAreNotSerialized(): void
    {
        $model = new ModelWithStatic();
        $model->id = 1;
        $model->name = 'Joao';

        $this->assertSame(['id' => 1, 'name' => 'Joao'], Serialize::from($model)->toArray());
    }

    public function testStaticPropertyIsNotCopiedFromAnArray(): void
    {
        ModelWithStatic::$instances = 7;
        $model = new ModelWithStatic();

        ObjectCopy::copy(['id' => 1, 'instances' => 99, 'INSTANCES' => 98], $model);

        $this->assertSame(1, $model->id);
        $this->assertSame(7, ModelWithStatic::$instances);
        $this->assertSame(['id' => 1, 'name' => null], get_object_vars($model));
    }

    public function testStaticPropertyIsNotCopiedFromAnObject(): void
    {
        ModelWithStatic::$instances = 7;
        $source = new ModelWithStatic();
        $source->id = 2;
        $target = new ModelWithStatic();

        ObjectCopy::copy($source, $target);

        $this->assertSame(2, $target->id);
        $this->assertSame(7, ModelWithStatic::$instances);
        $this->assertSame(['id' => 2, 'name' => null], get_object_vars($target));
    }
}
