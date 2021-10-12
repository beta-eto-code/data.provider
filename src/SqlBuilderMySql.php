<?php

namespace Data\Provider;

use Data\Provider\Interfaces\QueryCriteriaInterface;
use Data\Provider\Interfaces\SqlQueryInterface;

class SqlBuilderMySql extends SqlBuilderBase
{
    /**
     * @param QueryCriteriaInterface $queryCriteria
     * @param string $tableName
     * @param bool $usePlaceholder
     * @return SqlQueryInterface
     */
    public function buildSelectQuery(
        QueryCriteriaInterface $queryCriteria,
        string $tableName,
        bool $usePlaceholder = false
    ): SqlQueryInterface
    {
        $selectBlock = $this->buildSelectBlock($queryCriteria, $tableName, $usePlaceholder);
        $joinBlock = $this->buildJoinBlock($queryCriteria, $tableName, $usePlaceholder);
        $whereBlock = $this->buildWhereBlock($queryCriteria, $usePlaceholder);
        $orderBlock = $this->buildOrderBlock($queryCriteria->getOrderBy(), $usePlaceholder);
        $limitOffsetBlock = $this->buildLimitOffsetBlock($queryCriteria, $usePlaceholder);
        $groupBlock = $this->buildGroupBlock($queryCriteria, $usePlaceholder);

        $values = $selectBlock->getValues();
        $values = array_merge($values, $joinBlock->getValues());
        $values = array_merge($values, $whereBlock->getValues());
        $values = array_merge($values, $orderBlock->getValues());
        $values = array_merge($values, $limitOffsetBlock->getValues());
        $values = array_merge($values, $groupBlock->getValues());

        $keys = $selectBlock->getKeys();
        $keys = array_merge($keys, $joinBlock->getKeys());
        $keys = array_merge($keys, $whereBlock->getKeys());
        $keys = array_merge($keys, $orderBlock->getKeys());
        $keys = array_merge($keys, $limitOffsetBlock->getKeys());
        $keys = array_merge($keys, $groupBlock->getKeys());

        $sql = "{$selectBlock} {$joinBlock} {$whereBlock} {$orderBlock} {$limitOffsetBlock} {$groupBlock}";

        return new SqlQuery($sql, $values, $keys);
    }

    /**
     * @param QueryCriteriaInterface $queryCriteria
     * @param string $tableName
     * @param bool $usePlaceholder
     * @return SqlQueryInterface
     */
    public function buildDeleteQuery(
        QueryCriteriaInterface $queryCriteria,
        string $tableName,
        bool $usePlaceholder = false
    ): SqlQueryInterface
    {
        $joinBlock = $this->buildJoinBlock($queryCriteria, $tableName, $usePlaceholder);
        $whereBlock = $this->buildWhereBlock($queryCriteria, $usePlaceholder);
        $orderBlock = $this->buildOrderBlock($queryCriteria->getOrderBy(), $usePlaceholder);
        $limitOffsetBlock = $this->buildLimitOffsetBlock($queryCriteria, $usePlaceholder);

        $sql = "DELETE FROM {$tableName} {$joinBlock} {$whereBlock} {$orderBlock} {$limitOffsetBlock}";

        $values = $joinBlock->getValues();
        $values = array_merge($values, $whereBlock->getValues());
        $values = array_merge($values, $orderBlock->getValues());
        $values = array_merge($values, $limitOffsetBlock->getValues());

        $keys = $joinBlock->getKeys();
        $keys = array_merge($keys, $whereBlock->getKeys());
        $keys = array_merge($keys, $orderBlock->getKeys());
        $keys = array_merge($keys, $limitOffsetBlock->getKeys());

        return new SqlQuery($sql, $values, $keys);
    }

    /**
     * @param array $data
     * @param string $tableName
     * @param bool $usePlaceholder
     * @return SqlQueryInterface
     */
    public function buildInsertQuery(array $data, string $tableName, bool $usePlaceholder = false): SqlQueryInterface
    {
        $keys = array_keys($data);
        $values = array_values($data);
        $strValues = $this->prepareCauseValue($values, $usePlaceholder);

        $sql = "INSERT INTO {$tableName} (".implode(',', $keys).") VALUES {$strValues}";

        return new SqlQuery($sql, $values, $keys);
    }

    /**
     * @param QueryCriteriaInterface $queryCriteria
     * @param array $data
     * @param string $tableName
     * @param bool $usePlaceholder
     * @return SqlQueryInterface
     */
    public function buildUpdateQuery(
        QueryCriteriaInterface $queryCriteria,
        array $data,
        string $tableName,
        bool $usePlaceholder = false
    ): SqlQueryInterface
    {
        $joinBlock = $this->buildJoinBlock($queryCriteria, $tableName, $usePlaceholder);
        $whereBlock = $this->buildWhereBlock($queryCriteria, $usePlaceholder);
        $orderBlock = $this->buildOrderBlock($queryCriteria->getOrderBy(), $usePlaceholder);
        $limitOffsetBlock = $this->buildLimitOffsetBlock($queryCriteria, $usePlaceholder);

        $values = $whereBlock->getValues();
        $values = array_merge($values, $data);
        $values = array_merge($values, $whereBlock->getValues());
        $values = array_merge($values, $orderBlock->getValues());
        $values = array_merge($values, $limitOffsetBlock->getValues());

        $keys = $whereBlock->getKeys();
        $keys = array_merge($keys, $data);
        $keys = array_merge($keys, $whereBlock->getKeys());
        $keys = array_merge($keys, $orderBlock->getKeys());
        $keys = array_merge($keys, $limitOffsetBlock->getKeys());

        $setList = [];
        foreach ($data as $key => $value) {
            $setList[] = "{$key} = ".$this->prepareCauseValue($value, $usePlaceholder);
        }

        $sql = "UPDATE {$tableName} {$joinBlock} SET ".implode(', ', $setList). " {$whereBlock} {$orderBlock} {$limitOffsetBlock}";

        return new SqlQuery($sql, $values, $keys);
    }
}