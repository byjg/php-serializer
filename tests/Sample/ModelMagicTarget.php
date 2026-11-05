<?php

namespace Tests\Sample;

/**
 * The properties are magic: __isset() and __set()
 */
class ModelMagicTarget
{
    public array $data = ['known' => 'old'];

    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }
}
