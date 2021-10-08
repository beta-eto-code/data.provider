<?php


namespace Data\Provider\Interfaces;


use Closure;

interface DataProviderInterface
{
    /**
     * @param Closure $mapper - function(array $data): array
     * @return void
     */
    public function setMapperForRead(Closure $mapper);

    /**
     * @param Closure $mapper - function(array $data): array
     * @return void
     */
    public function setMapperForSave(Closure $mapper);

    /**
     * @return string
     */
    public function getSourceName(): string;

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
     * @param QueryCriteriaInterface|null $query
     * @return PkOperationResultInterface
     */
    public function save(array $data, QueryCriteriaInterface $query = null): PkOperationResultInterface;

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
     * @param array $data
     * @return void
     */
    public function clearPk(array &$data);

    /**
     * @param array $data
     * @return mixed
     */
    public function getPkValue(array $data);
}