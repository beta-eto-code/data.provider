<?php

namespace Data\Provider;

use Data\Provider\Interfaces\AssertableDataInterface;
use Data\Provider\Interfaces\CompareRuleGroupInterface;
use Data\Provider\Interfaces\CompareRuleInterface;

abstract class BaseCompareRuleGroup implements CompareRuleInterface, CompareRuleGroupInterface, AssertableDataInterface
{
    /**
     * @var CompareRuleInterface[]
     */
    private $ruleList = [];
    public function __construct(CompareRuleInterface $firstRule)
    {
        $this->addCompareRule($firstRule);
    }

    public function getKey(): string
    {
        return $this->getFirstRule()->getKey();
    }

    public function getAlias(): ?string
    {
        return $this->getFirstRule()->getAlias();
    }

    public function getOperation(): string
    {
        return $this->getFirstRule()->getOperation();
    }

    public function getCompareValue()
    {
        return $this->getFirstRule()->getCompareValue();
    }

    private function getFirstRule(): CompareRuleInterface
    {
        return current($this->ruleList);
    }

    public function isComplex(): bool
    {
        return count($this->ruleList) > 1;
    }

    public function getList(): array
    {
        return $this->ruleList;
    }

    public function add(string $name, string $operation, $value, ?string $alias = null): CompareRuleInterface
    {
        $compareRule = static::createCompareRule($name, $operation, $value, $alias);
        $this->addCompareRule($compareRule);
        return $compareRule;
    }

    /**
     * @param string $name
     * @param string $operation
     * @param mixed $value
     * @param string|null $alias
     * @return CompareRuleInterface
     */
    protected static function createCompareRule(
        string $name,
        string $operation,
        $value,
        ?string $alias = null
    ): CompareRuleInterface {
        return new CompareRule($name, $operation, $value, $alias);
    }

    public function addCompareRule(CompareRuleInterface $compareRule)
    {
        $this->ruleList[] = $compareRule;
    }
}
