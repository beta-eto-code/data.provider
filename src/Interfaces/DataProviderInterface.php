<?php


namespace Data\Provider\Interfaces;


use Closure;

interface DataProviderInterface
{
    /**
     * @param callable $mapper - function(array $data): array
     * @return void
     */
    public function setMapperForRead(callable $mapper);

    /**
     * @param callable $mapper - function(array $data): array
     * @return void
     */
    public function setMapperForSave(callable $mapper);

    /**
     * @return string
     */
    public function getSourceName(): string;

    /**
     * @param QueryCriteriaInterface|null $query
     * @return array
     */
    public function getData(QueryCriteriaInterface $query = null): array;

    /**
     * @param QueryCriteriaInterface $query
     * @return \Iterator
     */
    public function getIterator(QueryCriteriaInterface $query): \Iterator;

    /**
     * @param QueryCriteriaInterface|null $query
     * @return int
     */
    public function getDataCount(QueryCriteriaInterface $query = null): int;

    /**
     * @param array|\ArrayObject $data
     * @param QueryCriteriaInterface|null $query
     * @return PkOperationResultInterface
     */
    public function save(&$data, QueryCriteriaInterface $query = null): PkOperationResultInterface;

    /**
     * @param QueryCriteriaInterface $query
     * @return OperationResultInterface
     */
    public function remove(QueryCriteriaInterface $query): OperationResultInterface;

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

    /**
     * @return string|null
     */
    public function getPkName(): ?string;

    /**
     * @param array|\ArrayObject $data
     * @return void
     */
    public function clearPk(&$data);

    /**
     * @param array|\ArrayObject $data
     * @return mixed
     */
    public function getPkValue($data);

    /**
     * @param array|\ArrayObject $data
     * @return QueryCriteriaInterface|null
     */
    public function createPkQuery($data): ?QueryCriteriaInterface;
}