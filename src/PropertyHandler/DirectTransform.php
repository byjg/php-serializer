<?php

namespace ByJG\Serializer\PropertyHandler;

use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;
use Closure;

/**
 * DirectTransform Property Handler
 * 
 * A basic implementation of PropertyHandlerInterface that passes properties through unchanged.
 * This class provides:
 * - Identity mapping of property names (no changes)
 * - Optional value transformation through a closure
 * - Base class for more complex property handlers
 */
class DirectTransform implements PropertyHandlerInterface
{

    /**
     * @param Closure|null $valueHandler Optional closure to handle value transformation
     */
    public function __construct(protected ?Closure $valueHandler = null)
    {}

    /**
     * @inheritDoc
     */
    #[\Override]
    public function mapName(string $property): string
    {
        return $property;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function transformValue(string $propertyName, string $targetName, mixed $value, mixed $instance = null): mixed
    {
        if ($this->valueHandler !== null) {
            return ($this->valueHandler)($propertyName, $targetName, $value, $instance);
        }

        return $value;
    }
}