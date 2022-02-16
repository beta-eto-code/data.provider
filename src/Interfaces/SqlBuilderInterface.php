<?php

namespace Data\Provider\Interfaces;

interface SqlBuilderInterface
{
    /**
     * @param QueryCriteriaInterface $queryCriteria
     * @param string $tableName
     * @param bool $usePlaceholder
     * @return SqlQueryInterface
     */
    public function buildSelectBlock(
        QueryCriteriaInterface $queryCriteria,
        string $tableName,
        bool $usePlaceholder = false
    ): SqlQueryInterface;

    /**
     * @param QueryCriteriaInterface $queryCriteria
     * @param bool $usePlaceholder
     * @return SqlQueryInterface
     */
    public function buildWhereBlock(
        QueryCriteriaInterface $queryCriteria,
        bool $usePlaceholder = false
    ): SqlQueryInterface;

    /**
     * @param QueryCriteriaInterface $queryCriteria
     * @param bool $usePlaceholder
     * @return SqlQueryInterface
     */
    public function buildLimitOffsetBlock(
        QueryCriteriaInterface $queryCriteria,
        bool $usePlaceholder = false
    ): SqlQueryInterface;

    /**
     * @param QueryCriteriaInterface $queryCriteria
     * @param bool $usePlaceholder
     * @return SqlQueryInterface
     */
    public function buildGroupBlock(
        QueryCriteriaInterface $queryCriteria,
        bool $usePlaceholder = false
    ): SqlQueryInterface;

    /**
     * @param QueryCriteriaInterface $queryCriteria
     * @param string $tableName
     * @param bool $usePlaceholder
     * @return SqlQueryInterface
     */
    public function buildJoinBlock(
        QueryCriteriaInterface $queryCriteria,
        string $tableName,
        bool $usePlaceholder = false
    ): SqlQueryInterface;

    /**
     * @param OrderRuleInterface $orderRule
     * @param bool $usePlaceholder
     * @return SqlQueryInterface
     */
    public function buildOrderBlock(OrderRuleInterface $orderRule, bool $usePlaceholder = false): SqlQueryInterface;

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
    ): SqlQueryInterface;

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
    ): SqlQueryInterface;

    /**
     * @param array $data
     * @param string $tableName
     * @param bool $usePlaceholder
     * @return SqlQueryInterface
     */
    public function buildInsertQuery(array $data, string $tableName, bool $usePlaceholder = false): SqlQueryInterface;

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
    ): SqlQueryInterface;
}
