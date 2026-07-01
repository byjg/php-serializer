<?php

namespace Tests\Sample;

/**
 * A model with non-nullable typed properties without a default value. Until one of them is
 * assigned it stays in the "uninitialized" state, which cannot be read directly without raising
 * "Typed property ... must not be accessed before initialization".
 */
class ModelUninitializedTypedProperty
{
    public int $id;

    public string $name;
}