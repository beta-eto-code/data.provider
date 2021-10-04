<?php

namespace Data\Provider\Interfaces;

interface SqlQueryInterface
{
    /**
     * @return string
     */
    public function __toString(): string;

    /**
     * @return array
     */
    public function getValues(): array;

    /**
     * @return string[]
     */
    public function getKeys(): array;

    /**
     * @return bool
     */
    public function isEmpty(): bool;
}