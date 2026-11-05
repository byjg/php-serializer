<?php

namespace Tests\Sample;

/**
 * A model with static properties (class state, not data): public, and protected with a getter
 */
class ModelWithStatic
{
    public static int $instances = 0;

    protected static string $table = 'model';

    public $id;

    public $name;

    public static function getTable(): string
    {
        return self::$table;
    }
}
