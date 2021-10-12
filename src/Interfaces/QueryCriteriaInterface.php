<?php


namespace Data\Provider\Interfaces;


interface QueryCriteriaInterface
{
    /**
     * @param string[] $keys
     * @return void
     */
    public function setSelect(array $keys);

    /**
     * @return array
     */
    public function getSelect(): array;

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
    ): JoinRuleInterface;

    /**
     * @return JoinRuleInterface[]
     */
    public function getJoinList(): array;

    /**
     * @param array $group
     * @return void
     */
    public function setGroup(array $group);

    /**
     * @return array
     */
    public function getGroup(): array;

    /**
     * @param int $limit
     * @return void
     */
    public function setLimit(int $limit);

    /**
     * @return int
     */
    public function getLimit(): int;

    /**
     * @param int $offset
     * @return void
     */
    public function setOffset(int $offset);

    /**
     * @return int
     */
    public function getOffset(): int;

    /**
     * @param string $name
     * @param bool $isAscending
     * @return mixed
     */
    public function setOrderBy(string $name, bool $isAscending = true);

    /**
     * @return OrderRuleInterface
     */
    public function getOrderBy(): OrderRuleInterface;

    /**
     * @param string $name
     * @param string $operation
     * @param $value
     * @return CompareRuleInterface
     */
    public function addCriteria(string $name, string $operation, $value): CompareRuleInterface;

    /**
     * @param string $name
     * @param string $operation
     * @return bool
     */
    public function hasCriteria(string $name, string $operation): bool;

    /**
     * @return DataCheckerInterface
     */
    public function createDataChecker(): DataCheckerInterface;

    /**
     * @return bool
     */
    public function isEmptyCriteria(): bool;

    /**
     * @return CompareRuleInterface[]
     */
    public function getCriteriaList(): array;

    /**
     * @return string
     */
    public function getHash(): string;
}