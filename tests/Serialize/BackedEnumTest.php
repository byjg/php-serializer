<?php

namespace Tests\Serialize;

use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\PropertyHandler\DirectTransform;
use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use ByJG\Serializer\Serialize;
use PHPUnit\Framework\TestCase;
use stdClass;
use Tests\Sample\ModelEnum;
use Tests\Sample\SampleLevel;
use Tests\Sample\SampleStatus;
use Tests\Sample\SampleSuit;
use ValueError;

/**
 * A backed enum is its value on the way out (as json_encode() writes it), and is rebuilt from
 * that value on the way in, when the target property or setter is typed with the enum.
 */
class BackedEnumTest extends TestCase
{
    private function model(): ModelEnum
    {
        $model = new ModelEnum();
        $model->status = SampleStatus::Paid;
        $model->level = SampleLevel::High;
        $model->setPrevious(SampleStatus::Failed);
        return $model;
    }

    public function testToArrayWritesTheValue(): void
    {
        $this->assertSame(
            ['status' => 'PAID', 'level' => 2, 'previous' => 'FAILED'],
            Serialize::from($this->model())->withDoNotParseNullValues()->toArray()
        );
    }

    public function testTheFirstLevelAlsoWritesTheValue(): void
    {
        // What micro-orm saves: the database layer gets "PAID", not the enum object
        $this->assertSame(
            ['status' => 'PAID', 'level' => 2, 'previous' => 'FAILED'],
            Serialize::from($this->model())->withStopAtFirstLevel()->withDoNotParseNullValues()->toArray()
        );
    }

    public function testToJsonWritesTheValueLikeJsonEncode(): void
    {
        $this->assertSame('{"status":"PAID","level":2,"previous":"FAILED"}', Serialize::from($this->model())->withDoNotParseNullValues()->toJson());
        $this->assertSame(json_encode(['status' => SampleStatus::Paid]), Serialize::from(['status' => SampleStatus::Paid])->toJson());
    }

    public function testAPureEnumHasNoValueAndIsSerializedAsBefore(): void
    {
        $model = new ModelEnum();
        $model->suit = SampleSuit::Hearts;

        $this->assertSame(['name' => 'Hearts'], Serialize::from($model)->toArray()['suit']);
    }

    public function testCopyBuildsTheEnumOfAPropertyFromItsValue(): void
    {
        $model = new ModelEnum();
        ObjectCopy::copy(['status' => 'PAID', 'level' => 2], $model);

        $this->assertSame(SampleStatus::Paid, $model->status);
        $this->assertSame(SampleLevel::High, $model->level);
    }

    public function testCopyBuildsTheEnumOfASetterFromItsValue(): void
    {
        $model = new ModelEnum();
        ObjectCopy::copy(['previous' => 'FAILED'], $model);

        $this->assertSame(SampleStatus::Failed, $model->getPrevious());
    }

    public function testCopyMatchesThePropertyNameCaseInsensitively(): void
    {
        $model = new ModelEnum();
        ObjectCopy::copy(['STATUS' => 'PAID'], $model);

        $this->assertSame(SampleStatus::Paid, $model->status);
    }

    public function testCopyFromAnObjectKeepsTheEnum(): void
    {
        $source = new stdClass();
        $source->status = 'FAILED';
        $model = new ModelEnum();
        ObjectCopy::copy($source, $model);
        $this->assertSame(SampleStatus::Failed, $model->status);

        $copy = new ModelEnum();
        ObjectCopy::copy($this->model(), $copy);
        $this->assertSame(SampleStatus::Paid, $copy->status);
        $this->assertSame(SampleLevel::High, $copy->level);
        $this->assertSame(SampleStatus::Failed, $copy->getPrevious());
    }

    public function testCopyKeepsNullAndAnEnumAsTheyAre(): void
    {
        $model = $this->model();
        ObjectCopy::copy(['status' => null, 'level' => SampleLevel::Low], $model);

        $this->assertNull($model->status);
        $this->assertSame(SampleLevel::Low, $model->level);
    }

    public function testCopyLeavesAPropertyThatAlsoTakesAStringAlone(): void
    {
        $model = new ModelEnum();
        ObjectCopy::copy(['loose' => 'PAID'], $model);

        $this->assertSame('PAID', $model->loose);
    }

    public static function copySourceProvider(): array
    {
        $enums = ['status' => SampleStatus::Paid, 'previous' => SampleStatus::Failed, 'loose' => SampleStatus::Paid];
        $values = ['status' => 'PAID', 'previous' => 'FAILED', 'loose' => 'PAID'];

        $cases = [];
        foreach (['no handler' => null, 'handler' => new DirectTransform()] as $handlerName => $handler) {
            $cases["array of enums, $handlerName"] = [$enums, $handler];
            $cases["array of values, $handlerName"] = [$values, $handler];
            $cases["object of enums, $handlerName"] = [(object)$enums, $handler];
            $cases["object of values, $handlerName"] = [(object)$values, $handler];
        }
        return $cases;
    }

    /**
     * Every copy path gives the same result: the enum for a member typed with it (property or
     * setter), the value for anything that also takes a scalar (string|Enum, stdClass, array).
     */
    #[DataProvider('copySourceProvider')]
    public function testCopyGivesTheSameResultOnEveryPath(array|object $source, ?PropertyHandlerInterface $handler): void
    {
        $model = new ModelEnum();
        ObjectCopy::copy($source, $model, $handler);
        $this->assertSame(SampleStatus::Paid, $model->status);
        $this->assertSame(SampleStatus::Failed, $model->getPrevious());
        $this->assertSame('PAID', $model->loose);

        $object = new stdClass();
        ObjectCopy::copy($source, $object, $handler);
        $this->assertSame('PAID', $object->status);
        $this->assertSame('FAILED', $object->previous);

        $array = [];
        ObjectCopy::copy($source, $array, $handler);
        $this->assertSame(['status' => 'PAID', 'previous' => 'FAILED', 'loose' => 'PAID'], $array);
    }

    public function testCopyRejectsAValueTheEnumDoesNotHave(): void
    {
        $this->expectException(ValueError::class);

        $model = new ModelEnum();
        ObjectCopy::copy(['status' => 'REFUNDED'], $model);
    }
}
