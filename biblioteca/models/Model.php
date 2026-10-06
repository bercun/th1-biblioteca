<?php
//Model base class to access protected properties
abstract class Model
{
    //Check if attribute is set
    public function __isset(string $name): bool
    {
        return $this->hasAttribute($name) && isset($this->$name);
    }

    //Get attribute value
    public function __get(string $name): mixed
    {
        if (!$this->hasAttribute($name)) {
            throw new RuntimeException(
                static::class . " has no attribute \"{$name}\""
            );
        }

        return $this->$name;
    }

    public function __set(string $name, mixed $value): void
    {
        if (!$this->hasAttribute($name)) {
            throw new RuntimeException(
                static::class . " has no attribute \"{$name}\""
            );
        }

        $this->$name = $value;
    }

    //Check if attribute exists
    private function hasAttribute(string $name): bool
    {
        if ($name === 'db' || !property_exists($this, $name)) {
            return false;
        }

        $property = new ReflectionProperty($this, $name);

        return $property->isProtected();
    }
}
