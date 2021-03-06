<?php

namespace Data\Provider\Tests;

use Data\Provider\Interfaces\CompareRuleInterface;
use Data\Provider\QueryCriteria;
use Data\Provider\SqlBuilderMySql;
use PHPUnit\Framework\TestCase;

class SqlBuilderMySqlTest extends TestCase
{

    public function testBuildInsertQuery()
    {
        $dataForInsert = [
            'id' => 1,
            'title' => 'title 1'
        ];
        $sqlBuilder = new SqlBuilderMySql();
        $sqlQuery = $sqlBuilder->buildInsertQuery($dataForInsert, 'some_table');

        $this->assertEquals(['id', 'title'], $sqlQuery->getKeys());
        $this->assertEquals([1, 'title 1'], $sqlQuery->getValues());
        $this->assertEquals("INSERT INTO some_table (id,title) VALUES (1,'title 1')", (string)$sqlQuery);

        $placeholderSqlQuery = $sqlBuilder->buildInsertQuery($dataForInsert, 'some_table', true);
        $this->assertEquals(
            "INSERT INTO some_table (id,title) VALUES (?,?)",
            (string)$placeholderSqlQuery
        );
    }

    public function testBuildUpdateQuery()
    {
        $dataForUpdate = [
            'title' => 'title 1',
            'sort' => 20
        ];
        $sqlBuilder = new SqlBuilderMySql();

        $query = new QueryCriteria();
        $query->addCriteria('id', CompareRuleInterface::MORE, 1);
        $query->setLimit(2);
        $sqlQuery = $sqlBuilder->buildUpdateQuery($query, $dataForUpdate, 'some_table');

        $this->assertEquals(['title', 'sort', 'id', 'limit'], $sqlQuery->getKeys());
        $this->assertEquals(['title 1', 20, 1, 2], $sqlQuery->getValues());
        $this->assertEquals(
            "UPDATE some_table SET title = 'title 1', sort = 20 WHERE id > 1 LIMIT 2",
            (string)$sqlQuery
        );

        $placeholderSqlQuery = $sqlBuilder->buildUpdateQuery(
            $query,
            $dataForUpdate,
            'some_table',
            true
        );

        $this->assertEquals(
            "UPDATE some_table SET title = ?, sort = ? WHERE id > 1 LIMIT 2",
            (string)$placeholderSqlQuery
        );
    }

    public function testBuildSelectQuery()
    {
        $sqlBuilder = new SqlBuilderMySql();
        $query = new QueryCriteria();
        $query->setSelect(['id', 'title', 'sort']);
        $query->addCriteria('id', CompareRuleInterface::MORE, 1);
        $query->setLimit(2);
        $sqlQuery = $sqlBuilder->buildSelectQuery($query, 'some_table');

        $this->assertEquals(['select_1', 'select_2', 'select_3', 'id', 'limit'], $sqlQuery->getKeys());
        $this->assertEquals(['id', 'title', 'sort', 1, 2], $sqlQuery->getValues());
        $this->assertEquals(
            "SELECT id, title, sort FROM some_table WHERE id > 1 LIMIT 2",
            (string)$sqlQuery
        );
    }

    public function testBuildDeleteQuery()
    {
        $sqlBuilder = new SqlBuilderMySql();
        $query = new QueryCriteria();
        $query->addCriteria('id', CompareRuleInterface::MORE, 1);
        $query->setLimit(2);
        $sqlQuery = $sqlBuilder->buildDeleteQuery($query, 'some_table');

        $this->assertEquals(['id', 'limit'], $sqlQuery->getKeys());
        $this->assertEquals([1, 2], $sqlQuery->getValues());$this->assertEquals(
        "DELETE FROM some_table WHERE id > 1 LIMIT 2",
        (string)$sqlQuery
    );
    }
}
