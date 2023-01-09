<?php

namespace Data\Provider\Interfaces;

interface CompareRuleGroupInterface
{
    /**
     * @return CompareRuleInterface[]
     */
    public function getList(): array;

    /**
     * @param string $name
     * @param string $operation
     * @param mixed $value
     * @param string|null $alias
     * @return CompareRuleInterface
     */
    public function add(string $name, string $operation, $value, ?string $alias = null): CompareRuleInterface;

    /**
     * @param CompareRuleInterface $compareRule
     * @return void
     */
    public function addCompareRule(CompareRuleInterface $compareRule);
}
