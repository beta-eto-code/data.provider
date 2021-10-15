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
     * @param bool $insertOnFailUpdate
     * @return MigrateResultInterface
     * @params bool $insertOnFailUpdate
     */
    public function runUpdate(QueryCriteria $query, $compareRule = null, bool $insertOnFailUpdate = false): MigrateResultInterface;
}