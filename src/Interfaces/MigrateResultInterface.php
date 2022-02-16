<?php

namespace Data\Provider\Interfaces;

interface MigrateResultInterface
{
    /**
     * @return QueryCriteriaInterface
     */
    public function getQuery(): QueryCriteriaInterface;

    /**
     * @return array
     */
    public function getSourceData(): array;

    /**
     * @return bool
     */
    public function hasErrors(): bool;

    /**
     * @return OperationResultInterface[]
     */
    public function getErrors(): array;

    /**
     * @return array
     */
    public function getUnimportedDataList(): array;

    /**
     * @return OperationResultInterface[]
     */
    public function getResultList(): array;
}
