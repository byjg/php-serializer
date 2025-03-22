<?php

namespace ByJG\Serializer;

use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;
use stdClass;

abstract class ObjectCopy implements ObjectCopyInterface
{
    /**
     * Copies properties from the source to this object
     * 
     * @param array|object $source The source object to copy from
     * @param PropertyHandlerInterface|null $propertyHandler The property handler
     */
    #[\Override]
    public function copyFrom(array|object $source, ?PropertyHandlerInterface $propertyHandler = null): void
    {
        ObjectCopy::copy($source, $this, $propertyHandler);
    }

    /**
     * Copies properties from this object to the target
     * 
     * @param array|object $target The target object to copy to
     * @param PropertyHandlerInterface|null $propertyHandler The property handler
     */
    #[\Override]
    public function copyTo(array|object $target, ?PropertyHandlerInterface $propertyHandler = null): void
    {
        ObjectCopy::copy($this, $target, $propertyHandler);
    }

    /**
     * Copy the properties from a source object to the properties matching to a target object
     *
     * @param object|array $source The source object
     * @param object|array $target The target object
     * @param PropertyHandlerInterface|null $propertyHandler The property handler
     * @return void
     */
    public static function copy(object|array $source, object|array $target, ?PropertyHandlerInterface $propertyHandler = null): void
    {
        $propNameLower = [];

        $sourceArray = Serialize::from($source)
            ->withStopAtFirstLevel()
            ->parseAttributes(
                function ($attribute, $value, $keyName, $propertyName, $getterName) use ($propertyHandler, $target, $propNameLower) {
                    // ----------------------------------------------
                    // Extract the target name
                    $targetName = $propertyName;
                    if (!is_null($propertyHandler)) {
                        $targetName = $propertyHandler->mapName($propertyName);
                        $value = $propertyHandler->transformValue($propertyName, $targetName, $value);
                    }

                    // ----------------------------------------------
                    // Set the value to the target
                    if (method_exists($target, 'set' . $targetName)) {
                        $target->{'set' . $targetName}($value);
                    } elseif (isset($target->{$targetName}) || $target instanceof stdClass) {
                        $target->{$targetName} = $value;
                    } else {
                        // Check if source property have property case name different from target
                        $className = get_class($target);
                        if (!isset($propNameLower[$className])) {
                            $propNameLower[$className] = [];

                            $classVars = get_class_vars($className);
                            foreach ($classVars as $varKey => $varValue) {
                                $propNameLower[$className][strtolower($varKey)] = $varKey;
                            }
                        }

                        $propLower = strtolower($targetName);
                        if (isset($propNameLower[$className][$propLower])) {
                            $target->{$propNameLower[$className][$propLower]} = $value;
                        }
                    }
                }
            );
    }
}
