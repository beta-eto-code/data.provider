<?php

namespace Data\Provider\Interfaces;

interface CompareRuleInterface
{
    public const LESS = '<';
    public const MORE = '>';
    public const EQUAL = '=';
    public const NOT = 'NOT';
    public const IN = 'IN';
    public const NOT_IN = 'NOT IN';
    public const LESS_OR_EQUAL = '<=';
    public const MORE_OR_EQUAL = '>=';
    public const LIKE = 'LIKE';
    public const NOT_LIKE = 'NOT LIKE';
    public const BETWEEN = 'BETWEEN';
    public const NOT_BETWEEN = 'NOT BETWEEN';

    /**
     * @return string
     */
    public function getKey(): string;

    /**
     * @return string|null
     */
    public function getAlias(): ?string;

    /**
     * @return string
     */
    public function getOperation(): string;

    /**
     * @return mixed
     */
    public function getCompareValue();

    /**
     * @param array $data
     * @return bool
     */
    public function assertWithData(array $data): bool;

    /**
     * @return CompareRuleInterface[]
     */
    public function getOrList(): array;

    /**
     * @return CompareRuleInterface[]
     */
    public function getAndList(): array;

    /**
     * @param string $name
     * @param string $operation
     * @param mixed $value
     * @param string|null $alias
     * @return CompareRuleInterface
     */
    public function or(string $name, string $operation, $value, ?string $alias = null): CompareRuleInterface;

    /**
     * @param CompareRuleInterface $compareRule
     * @return void
     */
    public function orCompareRule(CompareRuleInterface $compareRule);

    /**
     * @param string $name
     * @param string $operation
     * @param mixed $value
     * @param string|null $alias
     * @return CompareRuleInterface
     */
    public function and(string $name, string $operation, $value, ?string $alias = null): CompareRuleInterface;

    /**
     * @param CompareRuleInterface $compareRule
     * @return void
     */
    public function andCompareRule(CompareRuleInterface $compareRule);

    /**
     * @return boolean
     */
    public function isComplex(): bool;
}
