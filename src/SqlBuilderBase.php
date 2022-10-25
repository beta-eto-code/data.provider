<?php

namespace Data\Provider;

use Data\Provider\Interfaces\CompareRuleInterface;
use Data\Provider\Interfaces\OrderRuleInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;
use Data\Provider\Interfaces\SqlBuilderInterface;
use Data\Provider\Interfaces\SqlQueryInterface;
use Data\Provider\Interfaces\SqlRelationProviderInterface;
use DateTime;

abstract class SqlBuilderBase implements SqlBuilderInterface
{
    /**
     * @var string
     */
    private $placeholder;

    public function __construct(string $placeholder = '?')
    {
        $this->placeholder = $placeholder;
    }

    /**
     * @param QueryCriteriaInterface $queryCriteria
     * @param string $tableName
     * @param bool $usePlaceholder
     *
     * @return SqlQuery
     */
    public function buildSelectBlock(
        QueryCriteriaInterface $queryCriteria,
        string $tableName,
        bool $usePlaceholder = false
    ): SqlQueryInterface {
        $querySelect = $queryCriteria->getSelect();
        if (empty($querySelect)) {
            $sql = "SELECT * FROM {$tableName}";
            return new SqlQuery($sql);
        }

        $columns = $queryCriteria->getSelect();
        $fieldStrList = $this->getColumnsDefinition($columns);
        $strSelect = implode(', ', $fieldStrList);
        $sql = "SELECT {$strSelect} FROM {$tableName}";
        return new SqlQuery($sql);
    }

    /**
     * @param  mixed $columns
     * @return array
     */
    private function getColumnsDefinition(array $columns) : array
    {
        $result = [];
        foreach ($columns as $alias => $columnName) {
            if (is_string($alias)) {
                $result[] = "{$columnName} as {$alias}";
            }

            $result[] = $columnName;
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @param bool $usePlaceholder
     *
     * @return string
     */
    protected function prepareCauseValue($value, bool $usePlaceholder = false): string
    {
        if (is_string($value)) {
            return $usePlaceholder ? $this->placeholder : "'{$value}'";
        }

        if (is_int($value) || is_float($value)) {
            return $usePlaceholder ? $this->placeholder : "{$value}";
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return $usePlaceholder ? $this->placeholder : (string)$value;
        }

        if ($value instanceof DateTime) {
            return $usePlaceholder ? $this->placeholder : "'{$value->format('Y-m-d H:i:s')}'";
        }

        if (is_bool($value)) {
            return $usePlaceholder ? $this->placeholder : ($value === true ? 'true' : 'false');
        }

        if (is_array($value)) {
            $list = [];
            foreach ($value as $v) {
                $list[] = $this->prepareCauseValue($v, $usePlaceholder);
            }

            return $usePlaceholder ?
                '(' . implode(',', array_fill(0, count($list), $this->placeholder)) . ')' :
                '(' . implode(',', $list) . ')';
        }

        return $usePlaceholder ? '?' : 'NULL';
    }

    /**
     * @param CompareRuleInterface $compareRule
     * @param bool $usePlaceholder
     */
    protected function buildSimpleCompareRule(
        CompareRuleInterface $compareRule,
        bool $usePlaceholder = false
    ): SqlQuery {
        $alias = $compareRule->getAlias();
        $operation = $compareRule->getOperation();
        $compareValue = $compareRule->getCompareValue();
        $propertyName = !empty($alias) ? "{$alias}.{$compareRule->getKey()}" : $compareRule->getKey();
        switch ($operation) {
            case CompareRuleInterface::EQUAL:
                $sql = $propertyName . (is_null($compareValue) ?
                        ' IS NULL' :
                        " = " . $this->prepareCauseValue($compareValue, $usePlaceholder));
                return new SqlQuery($sql, [$compareValue], [$propertyName]);
            case CompareRuleInterface::NOT:
                $sql = $propertyName . (is_null($compareValue) ?
                        ' IS NOT NULL' :
                        " <> " . $this->prepareCauseValue($compareValue, $usePlaceholder));
                return new SqlQuery($sql, [$compareValue], [$propertyName]);
            case CompareRuleInterface::LIKE:
                if (!is_string($compareValue)) {
                    return new SqlQuery('');
                }

                $sql = $propertyName . ' LIKE ' . $this->prepareCauseValue($compareValue, $usePlaceholder);

                return new SqlQuery($sql, [$compareValue], [$propertyName]);
            case CompareRuleInterface::NOT_LIKE:
                if (!is_string($compareValue)) {
                    return new SqlQuery('');
                }

                $sql = $propertyName . ' NOT LIKE ' . $this->prepareCauseValue($compareValue, $usePlaceholder);

                return new SqlQuery($sql, [$compareValue], [$propertyName]);
            case CompareRuleInterface::LESS:
                if (is_null($compareValue)) {
                    return new SqlQuery('');
                }

                $sql = $propertyName . ' < ' . $this->prepareCauseValue($compareValue, $usePlaceholder);

                return new SqlQuery($sql, [$compareValue], [$propertyName]);
            case CompareRuleInterface::MORE:
                if (is_null($compareValue)) {
                    return new SqlQuery('');
                }

                $sql = $propertyName . ' > ' . $this->prepareCauseValue($compareValue, $usePlaceholder);

                return new SqlQuery($sql, [$compareValue], [$propertyName]);
            case CompareRuleInterface::LESS_OR_EQUAL:
                if (is_null($compareValue)) {
                    return new SqlQuery('');
                }

                $sql = $propertyName . ' <= ' . $this->prepareCauseValue($compareValue, $usePlaceholder);

                return new SqlQuery($sql, [$compareValue], [$propertyName]);
            case CompareRuleInterface::MORE_OR_EQUAL:
                if (is_null($compareValue)) {
                    return new SqlQuery('');
                }

                $sql = $propertyName . ' >= ' . $this->prepareCauseValue($compareValue, $usePlaceholder);

                return new SqlQuery($sql, [$compareValue], [$propertyName]);
            case CompareRuleInterface::IN:
                if (is_null($compareValue)) {
                    return new SqlQuery('');
                }
                
                $sql = $propertyName . ' IN ' . $this->prepareCauseValue($compareValue, $usePlaceholder);

                return new SqlQuery($sql, $compareValue, [$propertyName]);
            case CompareRuleInterface::NOT_IN:
                if (is_null($compareValue)) {
                    return new SqlQuery('');
                }

                $sql = $propertyName . ' NOT IN ' . $this->prepareCauseValue($compareValue, $usePlaceholder);

                return new SqlQuery($sql, $compareValue, [$propertyName]);
            case CompareRuleInterface::BETWEEN:
                $value = $compareValue;
                if (!is_array($value) || count($value) !== 2) {
                    return new SqlQuery('');
                }

                $firstValue = array_shift($value);
                $secondValue = array_shift($value);

                $sql =  $propertyName . ' BETWEEN ' .
                    $this->prepareCauseValue($firstValue, $usePlaceholder) . ' AND ' .
                    $this->prepareCauseValue($secondValue, $usePlaceholder);

                return new SqlQuery($sql, [$firstValue, $secondValue], [$propertyName, $propertyName]);
            case CompareRuleInterface::NOT_BETWEEN:
                $value = $compareValue;
                if (!is_array($value) || count($value) !== 2) {
                    return new SqlQuery('');
                }

                $firstValue = array_shift($value);
                $secondValue = array_shift($value);

                $sql =  $propertyName . 'NOT BETWEEN ' .
                    $this->prepareCauseValue($firstValue, $usePlaceholder) . ' AND ' .
                    $this->prepareCauseValue($secondValue, $usePlaceholder);

                return new SqlQuery($sql, [$firstValue, $secondValue], [$propertyName, $propertyName]);
        }

        return new SqlQuery('');
    }

    /**
     * @param CompareRuleInterface $compareRule
     * @param bool $usePlaceholder
     */
    protected function buildComplexCompareRule(
        CompareRuleInterface $compareRule,
        bool $usePlaceholder = false
    ): SqlQuery {
        $sqlQuery = $this->buildSimpleCompareRule($compareRule, $usePlaceholder);
        if (!$compareRule->isComplex()) {
            return $sqlQuery;
        }

        $sqlCause = (string)$sqlQuery;
        $values = $sqlQuery->getValues();
        $keys = $sqlQuery->getKeys();

        $sqlAndBlocks = [];
        foreach ($compareRule->getAndList() as $cr) {
            $sq = $this->buildComplexCompareRule($cr, $usePlaceholder);
            $sqlAndBlocks[] = (string)$sq;
            $values = array_merge($values, $sq->getValues());
            $keys = array_merge($keys, $sq->getKeys());
        }

        if (count($sqlAndBlocks) > 0) {
            $sqlCause = "(" . implode(' AND ', array_merge([$sqlCause], $sqlAndBlocks)) . ")";
        }

        $sqlOrBlocks = [];
        foreach ($compareRule->getOrList() as $cr) {
            $sq = $this->buildComplexCompareRule($cr, $usePlaceholder);
            $sqlOrBlocks[] = (string)$sq;
            $values = array_merge($values, $sq->getValues());
            $keys = array_merge($keys, $sq->getKeys());
        }

        if (count($sqlOrBlocks) > 0) {
            $sqlCause = "(" . implode(' OR ', array_merge([$sqlCause], $sqlOrBlocks)) . ")";
        }

        return new SqlQuery($sqlCause, $values, $keys);
    }

    /**
     * @param QueryCriteriaInterface $queryCriteria
     * @param bool $usePlaceholder
     *
     * @return SqlQuery
     */
    public function buildWhereBlock(
        QueryCriteriaInterface $queryCriteria,
        bool $usePlaceholder = false
    ): SqlQueryInterface {
        $buildList = [];
        $values = [];
        $keys = [];
        foreach ($queryCriteria->getCriteriaList() as $criteria) {
            $sq = $this->buildComplexCompareRule($criteria, $usePlaceholder);
            if (!$sq->isEmpty()) {
                $buildList[] = (string)$sq;
                $values = array_merge($values, $sq->getValues());
                $keys = array_merge($keys, $sq->getKeys());
            }
        }

        if (empty($buildList)) {
            return new SqlQuery('');
        }

        $sql = ' WHERE ' . implode(' AND ', $buildList);

        return new SqlQuery($sql, $values, $keys);
    }

    /**
     * @param QueryCriteriaInterface $queryCriteria
     * @param bool $usePlaceholder
     *
     * @return SqlQuery
     */
    public function buildLimitOffsetBlock(
        QueryCriteriaInterface $queryCriteria,
        bool $usePlaceholder = false
    ): SqlQueryInterface {
        $limit = $queryCriteria->getLimit();
        $offset = $queryCriteria->getOffset();

        $values = [];
        $keys = [];
        $result = '';
        if ($limit > 0) {
            $values[] = $limit;
            $keys[] = 'limit';
            $result .= "LIMIT {$limit}";
        }

        if ($offset > 0) {
            $values[] = $offset;
            $keys[] = 'offset';
            $result .= " OFFSET {$offset}";
        }

        return new SqlQuery($result, $values, $keys);
    }

    /**
     * @param QueryCriteriaInterface $queryCriteria
     * @param bool $usePlaceholder
     *
     * @return SqlQuery
     */
    public function buildGroupBlock(
        QueryCriteriaInterface $queryCriteria,
        bool $usePlaceholder = false
    ): SqlQueryInterface {
        $groupList = $queryCriteria->getGroup();
        if (empty($groupList)) {
            return new SqlQuery('');
        }

        $prepareValue = implode(', ', $groupList);
        $sql = "GROUP BY {$prepareValue}";

        return new SqlQuery($sql, [$groupList], ['group']);
    }

    /**
     * @param QueryCriteriaInterface $queryCriteria
     * @param string $tableName
     * @param bool $usePlaceholder
     *
     * @return SqlQuery
     */
    public function buildJoinBlock(
        QueryCriteriaInterface $queryCriteria,
        string $tableName,
        bool $usePlaceholder = false
    ): SqlQueryInterface {
        $joinStrList = [];
        $values = [];
        $keys = [];
        foreach ($queryCriteria->getJoinList() as $joinRule) {
            $joinDataProvider = $joinRule->getDataProvider();
            if (!($joinDataProvider instanceof SqlRelationProviderInterface)) {
                continue;
            }

            $alias = $joinRule->getAlias();
            $sourceName = $joinDataProvider->getTableName();
            $destKey = $joinRule->getDestKey();
            $foreignKey = $joinRule->getForeignKey();

            $buildList = [];
            $joinStr = "{$joinRule->getType()} JOIN {$sourceName} {$alias} ON " . ($alias ?? $sourceName)
                . ".{$destKey} = {$tableName}.{$foreignKey}";

            $joinQuery = $joinRule->getQueryCriteria();
            if ($joinQuery instanceof QueryCriteriaInterface) {
                foreach ($joinQuery->getCriteriaList() as $criteria) {
                    $sq = $this->buildComplexCompareRule($criteria, $usePlaceholder);
                    if (!$sq->isEmpty()) {
                        $buildList[] = (string)$sq;
                        $values = array_merge($values, $sq->getValues());
                        $keys = array_merge($keys, $sq->getKeys());
                    }
                }
            }

            if (!empty($buildList)) {
                $joinStr .= ' AND (' . implode(' AND ', $buildList) . ')';
            }

            $joinStrList[] = $joinStr;
        }

        $sql = implode(" ", $joinStrList);

        return new SqlQuery($sql, $values, $keys);
    }

    /**
     * @param OrderRuleInterface $orderRule
     * @param bool $usePlaceholder
     *
     * @return SqlQuery
     */
    public function buildOrderBlock(OrderRuleInterface $orderRule, bool $usePlaceholder = false): SqlQueryInterface
    {
        if ($orderRule->isEmpty()) {
            return new SqlQuery('');
        }

        $strList = [];
        foreach ($orderRule->getOrderData() as $key => $data) {
            $isAscending = (bool)$data['isAscending'];
            $alias = $data['alias'] ? "{$data['alias']}." : "";
            $strList[] = "{$alias}{$key} " . ($isAscending ? 'ASC' : 'DESC');
        }

        $sql = "ORDER BY " . implode(', ', $strList);

        return new SqlQuery($sql);
    }
}
