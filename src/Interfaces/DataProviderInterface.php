<?php


namespace Data\Provider\Interfaces;


use Closure;

interface DataProviderInterface
{
    /**
     * @return string
     */
    public function getSourceName(): string;

    /**
     * @return Closure|null - function(ModelInterface $model): array
     */
    public function getDataHandler(): ?Closure;

    /**
     * @param QueryCriteriaInterface $query
     * @return array
     */
    public function getData(QueryCriteriaInterface $query): array;

    /**
     * @param QueryCriteriaInterface $query
     * @return int
     */
    public function getDataCount(QueryCriteriaInterface $query): int;

    /**
     * @param array $data
     * @param mixed $pk
     * @return OperationResultInterface
     */
    public function save(array $data, $pk = null): OperationResultInterface;

    /**
     * @param mixed $pk
     * @return OperationResultInterface
     */
    public function remove($pk): OperationResultInterface;

    /**
     * @return bool
     */
    public function startTransaction(): bool;

    /**
     * @return bool
     */
    public function commitTransaction(): bool;

    /**
     * @return bool
     */
    public function rollbackTransaction(): bool;
}