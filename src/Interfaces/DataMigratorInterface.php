<?php

namespace Data\Provider\Interfaces;

interface DataMigratorInterface
{
    /**
     * @param QueryCriteriaInterface $query
     * @return MigrateResultInterface
     */
    public function run(QueryCriteriaInterface $query): MigrateResultInterface;
}