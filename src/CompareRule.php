<?php

namespace Data\Provider;

use Data\Provider\Interfaces\AssertableDataInterface;
use Data\Provider\Interfaces\CompareRuleGroupInterface;
use Data\Provider\Interfaces\CompareRuleInterface;
use Data\Provider\Interfaces\ComplexAndCompareRuleInterface;
use Data\Provider\Interfaces\ComplexOrCompareRuleInterface;
use Exception;

class CompareRule extends SimpleCompareRule implements ComplexAndCompareRuleInterface, ComplexOrCompareRuleInterface
{
    /**
     * @var CompareRuleGroupInterface|null
     */
    private $orGroup = null;
    /**
     * @var CompareRuleGroupInterface|null
     */
    private $andGroup = null;

    /**
     * @param string $name
     * @param string $operation
     * @param mixed $value
     * @param string|null $alias
     */
    public function __construct(string $name, string $operation, $value, ?string $alias = null)
    {
        parent::__construct($name, $operation, $value, $alias);
    }

    /**
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function assertWithData(array $data): bool
    {
        $result = parent::assertWithData($data);
        if ($result && ($this->andGroup instanceof AssertableDataInterface)) {
            $result = $this->andGroup->assertWithData($data);
        }

        if (!$result && ($this->orGroup instanceof AssertableDataInterface)) {
            $result = $this->orGroup->assertWithData($data);
        }

        return $result;
    }

    /**
     * @param string $name
     * @param string $operation
     * @param mixed $value
     * @param string|null $alias
     * @return self
     */
    public function or(string $name, string $operation, $value, ?string $alias = null): CompareRuleInterface
    {
        $compareRule = new CompareRule($name, $operation, $value, $alias);
        $this->orCompareRule($compareRule);
        return $compareRule;
    }

    /**
     * @param CompareRuleInterface $compareRule
     * @return void
     */
    public function orCompareRule(CompareRuleInterface $compareRule)
    {
        if ($this->orGroup instanceof CompareRuleGroupInterface) {
            $this->orGroup->addCompareRule($compareRule);
            return;
        }

        $this->orGroup = new OrCompareRuleGroup($compareRule);
    }

    /**
     * @param string $name
     * @param string $operation
     * @param mixed $value
     * @param string|null $alias
     * @return self
     */
    public function and(string $name, string $operation, $value, ?string $alias = null): CompareRuleInterface
    {
        $compareRule = new CompareRule($name, $operation, $value, $alias);
        $this->andCompareRule($compareRule);
        return $compareRule;
    }

    /**
     * @param CompareRuleInterface $compareRule
     * @return void
     */
    public function andCompareRule(CompareRuleInterface $compareRule)
    {
        if ($this->andGroup instanceof CompareRuleGroupInterface) {
            $this->andGroup->addCompareRule($compareRule);
            return;
        }

        $this->andGroup = new AndCompareRuleGroup($compareRule);
    }

    /**
     * @return boolean
     */
    public function isComplex(): bool
    {
        return !empty($this->orGroup) || !empty($this->andGroup);
    }

    /**
     * @return CompareRuleInterface[]
     */
    public function getOrList(): array
    {
        return $this->orGroup instanceof CompareRuleGroupInterface ? $this->orGroup->getList() : [];
    }

    /**
     * @return CompareRuleInterface[]
     *
     * @psalm-return array<CompareRuleInterface>
     */
    public function getAndList(): array
    {
        return $this->andGroup instanceof CompareRuleGroupInterface ? $this->andGroup->getList() : [];
    }
}
