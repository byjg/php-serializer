<?php

namespace Tests\Serialize;

use ByJG\Serializer\ObjectCopy;
use PHPUnit\Framework\TestCase;
use Tests\Sample\ModelDynamicTarget;
use Tests\Sample\ModelMagicTarget;
use Tests\Sample\ModelWriteTargets;
use Tests\Sample\SampleLevel;
use Tests\Sample\SampleStatus;

/**
 * How ObjectCopy writes each value into an object: the setter, else the public property (exact case, else any
 * case), else a property the instance has (dynamic, or magic __isset/__set); nothing otherwise
 */
class ObjectCopyWriteTest extends TestCase
{
    public function testSetterWinsOverThePublicProperty(): void
    {
        $target = new ModelWriteTargets();
        ObjectCopy::copy(['both' => 'x'], $target);
        $this->assertSame('setter:x', $target->both);
    }

    public function testPublicPropertyExactAndAnyCase(): void
    {
        $target = new ModelWriteTargets();
        ObjectCopy::copy(['Exact' => 'a', 'LOWER' => 'b'], $target);
        $this->assertSame('a', $target->Exact);
        $this->assertSame('b', $target->lower);

        // A null property (isset() is false) is written too, by its name in any case
        $target = new ModelWriteTargets();
        ObjectCopy::copy(['exact' => 'c', 'lower' => 'd'], $target);
        $this->assertSame('c', $target->Exact);
        $this->assertSame('d', $target->lower);
    }

    public function testSetterInAnyCase(): void
    {
        $target = new ModelWriteTargets();
        ObjectCopy::copy(['VIASETTER' => 'v'], $target);
        $this->assertSame('v', $target->getViaSetter());
    }

    public function testProtectedPropertyWithoutSetterIsNotWritten(): void
    {
        $target = new ModelWriteTargets();
        ObjectCopy::copy(['hidden' => 'changed', 'unknown' => 'x'], $target);
        $this->assertSame('hidden', $target->getHidden());
        $this->assertFalse(property_exists($target, 'unknown'));
    }

    public function testBackedEnumBySetterAndByProperty(): void
    {
        $target = new ModelWriteTargets();
        ObjectCopy::copy(['level' => 2, 'status' => 'PAID'], $target);
        $this->assertSame(SampleLevel::High, $target->getLevel());
        $this->assertSame(SampleStatus::Paid, $target->status);
    }

    public function testDynamicPropertyOfTheInstance(): void
    {
        $withDynamic = new ModelDynamicTarget();
        $withDynamic->extra = 'old';
        ObjectCopy::copy(['id' => 1, 'extra' => 'new'], $withDynamic);
        $this->assertSame(1, $withDynamic->id);
        $this->assertSame('new', $withDynamic->extra);

        // Another instance of the same class, without that dynamic property: not created
        $without = new ModelDynamicTarget();
        ObjectCopy::copy(['id' => 2, 'extra' => 'new'], $without);
        $this->assertSame(2, $without->id);
        $this->assertFalse(property_exists($without, 'extra'));
    }

    public function testWriterForGivesHowCopyWritesEachName(): void
    {
        $this->assertSame(['setboth', true, null], self::lower(ObjectCopy::writerFor(ModelWriteTargets::class, 'both')));
        $this->assertSame(['setViaSetter', true, null], ObjectCopy::writerFor(ModelWriteTargets::class, 'ViaSetter'));
        $this->assertSame(['setlevel', true, SampleLevel::class], self::lower(ObjectCopy::writerFor(ModelWriteTargets::class, 'level')));
        $this->assertSame(['Exact', false, null], ObjectCopy::writerFor(ModelWriteTargets::class, 'exact'));
        $this->assertSame(['status', false, SampleStatus::class], ObjectCopy::writerFor(ModelWriteTargets::class, 'STATUS'));
        $this->assertFalse(ObjectCopy::writerFor(ModelWriteTargets::class, 'hidden'));
        $this->assertFalse(ObjectCopy::writerFor(ModelDynamicTarget::class, 'extra'));
    }

    /**
     * The setter name as written in the name given (method names are case-insensitive)
     */
    private static function lower(array|false $writer): array|false
    {
        return $writer === false ? false : [strtolower($writer[0]), $writer[1], $writer[2]];
    }

    public function testMagicProperty(): void
    {
        $target = new ModelMagicTarget();
        ObjectCopy::copy(['known' => 'new', 'unknown' => 'x'], $target);
        $this->assertSame(['known' => 'new'], $target->data);
    }
}
