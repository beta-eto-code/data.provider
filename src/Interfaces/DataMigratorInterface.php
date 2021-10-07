<?php

namespace Data\Provider\Interfaces;

use Data\Provider\QueryCriteria;

interface DataMigratorInterface
{
    /**
     * @param QueryCriteriaInterface $query
     * @return MigrateResultInterface
     */
    public function runInsert(QueryCriteriaInterface $query): MigrateResultInterface;

    /**
     * @param QueryCriteria $query
     * @param Closure|string|null $compareRule - key for compare value or closure function(array $dataImport): QueryCriteriaInterface
     * @params bool $insertOnFailUpdate
     * @return MigrateResultInterface
     */
    public function runUpdate(QueryCriteria $query, $compareRule = null, bool $insertOnFailUpdate = false): MigrateResultInterface;
}