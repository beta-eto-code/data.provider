<?php

namespace Data\Provider;

use Data\Provider\Interfaces\SqlQueryInterface;

class SqlQuery implements SqlQueryInterface
{
    /**
     * @var string
     */
    private $sql;
    /**
     * @var array
     */
    private $values;
    /**
     * @var string[]
     */
    private $keys;

    public function __construct(string $sql, array $values = [], array $keys = [])
    {
        $this->sql = $sql;
        $this->values = $values;
        $this->keys = $keys;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return trim(str_replace('  ', ' ', $this->sql));
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @return string[]
     */
    public function getKeys(): array
    {
        return $this->keys;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->sql);
    }
}