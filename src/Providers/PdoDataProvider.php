<?php

namespace Data\Provider\Providers;

use Closure;
use Data\Provider\Interfaces\CompareRuleInterface;
use Data\Provider\Interfaces\OperationResultInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;
use Data\Provider\Interfaces\SqlBuilderInterface;
use Data\Provider\Interfaces\SqlRelationProviderInterface;
use Data\Provider\OperationResult;
use Data\Provider\QueryCriteria;
use PDO;

class PdoDataProvider extends BaseDataProvider implements SqlRelationProviderInterface
{
    /**
     * @var PDO
     */
    private $connection;
    /**
     * @var string
     */
    private $tableName;
    /**
     * @var SqlBuilderInterface
     */
    private $sqlBuilder;
    /**
     * @var Closure|null
     */
    private $dataHandler;

    public function __construct(
        PDO $connection,
        string $tableName,
        SqlBuilderInterface $sqlBuilder,
        string $pkName = null,
        ?Closure $dataHandler = null
    )
    {
        parent::__construct($pkName);
        $this->connection = $connection;
        $this->tableName = $tableName;
        $this->sqlBuilder = $sqlBuilder;
        $this->dataHandler = $dataHandler;
    }

    /**
     * @return string
     */
    public function getSourceName(): string
    {
        return $this->tableName;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @return Closure|null
     */
    public function getDataHandler(): ?Closure
    {
        return $this->dataHandler;
    }

    /**
     * @param QueryCriteriaInterface $query
     * @return array
     */
    protected function getDataInternal(QueryCriteriaInterface $query): array
    {
        $sqlQuery = $this->sqlBuilder->buildSelectQuery($query, $this->tableName, true);
        $sth = $this->connection->prepare((string)$sqlQuery);
        $sth->execute($sqlQuery->getValues());

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param QueryCriteriaInterface $query
     * @return int
     */
    public function getDataCount(QueryCriteriaInterface $query): int
    {
        $whereBlock = $this->sqlBuilder->buildWhereBlock($query, true);
        $sql = "SELECT COUNT(*) as cnt FROM {$this->tableName} {$whereBlock}";
        $sth = $this->connection->prepare($sql);
        $sth->execute($whereBlock->getValues());
        $data = $sth->fetch();

        return (int)$data['cnt'];
    }

    /**
     * @param array $data
     * @param QueryCriteriaInterface|null $query
     * @return OperationResultInterface
     */
    protected function saveInternal(array $data, QueryCriteriaInterface $query = null): OperationResultInterface
    {
        if (empty($query)) {
            $sqlQuery = $this->sqlBuilder->buildInsertQuery($data, $this->tableName, true);
            $sth = $this->connection->prepare((string)$sqlQuery);
            $isSuccess = $sth->execute($sqlQuery->getValues());

            return  $isSuccess ?
                new OperationResult(null, [
                    'data' => $data
                ]) :
                new OperationResult(
                    'Ошибка добавления записи:'.implode(', ', $sth->errorInfo()),
                    [
                        'data' => $data,
                        'query' => $query,
                    ]
                );
        }

        $sqlQuery = $this->sqlBuilder->buildUpdateQuery($query, $data, $this->tableName, true);
        $sth = $this->connection->prepare((string)$sqlQuery);
        $isSuccess = $sth->execute($sqlQuery->getValues());

        return  $isSuccess ?
            new OperationResult(
                null,
                [
                    'data' => $data,
                    'query' => $query,
                ]
            ) :
            new OperationResult(
                'Ошибка обновления записи:'.implode(', ', $sth->errorInfo()),
                [
                    'data' => $data,
                    'query' => $query,
                ]
            );
    }

    /**
     * @param QueryCriteriaInterface $query
     * @return OperationResultInterface
     */
    public function remove(QueryCriteriaInterface $query): OperationResultInterface
    {
        $sqlQuery = $this->sqlBuilder->buildDeleteQuery($query, $this->tableName, true);
        $sth = $this->connection->prepare((string)$sqlQuery);
        $isSuccess = $sth->execute($sqlQuery->getValues());

        return  $isSuccess ?
            new OperationResult(null, ['query' => $query]) :
            new OperationResult(
                'Ошибка обновления записи:'.implode(', ', $sth->errorInfo()),
                ['query' => $query]
            );
    }

    /**
     * @return bool
     */
    public function startTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * @return bool
     */
    public function commitTransaction(): bool
    {
        return $this->connection->commit();
    }

    /**
     * @return bool
     */
    public function rollbackTransaction(): bool
    {
        return $this->connection->rollBack();
    }
}