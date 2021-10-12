<?php

namespace Data\Provider;

use Data\Provider\Interfaces\CompareRuleInterface;
use Data\Provider\Interfaces\DataCheckerInterface;
use Data\Provider\Interfaces\DataProviderInterface;
use Data\Provider\Interfaces\JoinRuleInterface;
use Data\Provider\Interfaces\OrderRuleInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;

class QueryCriteria implements QueryCriteriaInterface
{
    /**
     * @var int
     */
    private $limit = 0;
    /**
     * @var int
     */
    private $offset = 0;
    /**
     * @var OrderRuleInterface|null
     */
    private $orderRule = null;

    /**
     * @var CompareRuleInterface[]
     */
    private $criteriaList = [];
    /**
     * @var string[]
     */
    private $select;
    /**
     * @var string[]
     */
    private $group;

    /**
     * @var JoinRuleInterface[]
     */
    private $joinList;

    public function __construct(CompareRuleInterface $compareRule = null)
    {
        if ($compareRule instanceof CompareRuleInterface) {
            $this->criteriaList[$compareRule->getKey().$compareRule->getOperation()] = $compareRule;
        }
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit)
    {
        $this->limit = $limit;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $offset
     */
    public function setOffset(int $offset)
    {
        $this->offset = $offset;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @param string $name
     * @param string $operation
     * @param $value
     * @param string|null $alias
     * @return CompareRuleInterface
     */
    public function addCriteria(string $name, string $operation, $value, ?string $alias = null): CompareRuleInterface
    {
        $compareRule = new CompareRule($name, $operation, $value, $alias);
        $this->criteriaList[$name.$operation] = $compareRule;

        return $compareRule;
    }

    /**
     * @return CompareRuleInterface[]
     */
    public function getCriteriaList(): array
    {
        return $this->criteriaList;
    }

    /**
     * @param string $name
     * @param bool $isAscending
     * @param string|null $alias
     * @return void
     */
    public function setOrderBy(string $name, bool $isAscending = true, ?string $alias = null)
    {
        $this->getOrderBy()->setOrderByKey($name, $isAscending, $alias);
    }

    /**
     * @return OrderRuleInterface
     */
    public function getOrderBy(): OrderRuleInterface
    {
        if ($this->orderRule instanceof OrderRuleInterface) {
            return $this->orderRule;
        }

        return $this->orderRule = new OrderRule;
    }

    /**
     * @param string[] $keys
     */
    public function setSelect(array $keys)
    {
        $this->select = $keys;
    }

    /**
     * @return array
     */
    public function getSelect(): array
    {
        return (array)$this->select;
    }

    /**
     * @param DataProviderInterface $dataProvider
     * @param string $destKey
     * @param string $foreignKey
     * @param QueryCriteriaInterface|null $query
     * @return JoinRuleInterface
     */
    public function addJoin(
        DataProviderInterface $dataProvider,
        string $destKey,
        string $foreignKey,
        ?QueryCriteriaInterface $query = null
    ): JoinRuleInterface
    {
        $join = new JoinRule($dataProvider, $foreignKey, $destKey, $query);
        $this->joinList[] = $join;

        return $join;
    }

    /**
     * @return array|JoinRuleInterface[]
     */
    public function getJoinList(): array
    {
        return $this->joinList ?? [];
    }

    /**
     * @param string[] $group
     */
    public function setGroup(array $group)
    {
        $this->group = $group;
    }

    /**
     * @return DataCheckerInterface
     */
    public function createDataChecker(): DataCheckerInterface
    {
        return new DefaultDataChecker($this);
    }

    /**
     * @return bool
     */
    public function isEmptyCriteria(): bool
    {
        return empty($this->criteriaList);
    }

    /**
     * @param string $name
     * @param string $operation
     * @return bool
     */
    public function hasCriteria(string $name, string $operation): bool
    {
        if (emtpy($this->criteriaList)) {
            return false;
        }

        foreach ($this->criteriaList as $compareRule) {
            if ($compareRule->getOperation() === $operation && $compareRule->getKey() === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getGroup(): array
    {
        return (array)$this->group;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return '';
    }
}