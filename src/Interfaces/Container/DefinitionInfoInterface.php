<?php
namespace Branch\Interfaces\Container;

interface DefinitionInfoInterface
{
    public function isTransient($definition): bool;

    public function isArrayObjectDefinition($definition): bool;

    public function isStringObjectDefinition($definition): bool;

    public function isInstanceDefinition($definition): bool;

    public function isClosureDefinition($definition): bool;

    public function isArrayDefinition($definition): bool;

    public function isScalarDefinition($definition): bool;

    public function isResourceDefinition($definition): bool;
}