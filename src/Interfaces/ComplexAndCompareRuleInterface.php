<?php

namespace Data\Provider\Interfaces;

interface ComplexAndCompareRuleInterface
{
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
    public function and(string $name, string $operation, $value, ?string $alias = null): CompareRuleInterface;

    /**
     * @param CompareRuleInterface $compareRule
     * @return void
     */
    public function andCompareRule(CompareRuleInterface $compareRule);
}
