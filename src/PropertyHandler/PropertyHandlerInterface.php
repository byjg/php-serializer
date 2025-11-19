<?php

namespace ByJG\Serializer\PropertyHandler;

interface PropertyHandlerInterface
{
    /**
     * Maps a source property name to a target property name
     *
     * @param string $property The source property name
     * @return string The target property name
     */
    public function mapName(string $property): string;

    /**
     * Changes the value being copied
     *
     * @param string $propertyName The source property name
     * @param string $targetName The target property name
     * @param mixed $value The value to be changed
     * @param mixed|null $instance The full source object instance (optional)
     * @return mixed The modified value
     */
    public function transformValue(string $propertyName, string $targetName, mixed $value, mixed $instance = null): mixed;
} 