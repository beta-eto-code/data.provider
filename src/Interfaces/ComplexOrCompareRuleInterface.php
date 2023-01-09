<?php

namespace Data\Provider\Interfaces;

interface ComplexOrCompareRuleInterface
{
    /**
     * @return CompareRuleInterface[]
     */
    public function getOrList(): array;

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
    public function or(string $name, string $operation, $value, ?string $alias = null): CompareRuleInterface;
}
