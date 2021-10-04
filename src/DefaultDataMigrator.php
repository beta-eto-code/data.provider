<?php

namespace Data\Provider;

use Data\Provider\Interfaces\DataMigratorInterface;
use Data\Provider\Interfaces\DataProviderInterface;
use Data\Provider\Interfaces\MigrateResultInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;

class DefaultDataMigrator implements DataMigratorInterface
{
    /**
     * @var DataProviderInterface
     */
    private $sourceProvider;
    /**
     * @var DataProviderInterface
     */
    private $targetProvider;

    public function __construct(DataProviderInterface $sourceProvider, DataProviderInterface $targetProvider)
    {
        $this->sourceProvider = $sourceProvider;
        $this->targetProvider = $targetProvider;
    }

    /**
     * @param QueryCriteriaInterface $query
     * @param string|null $sourcePkKey
     * @return MigrateResultInterface
     */
    public function run(QueryCriteriaInterface $query, string $sourcePkKey = null): MigrateResultInterface
    {
        $resultList = [];
        $dataForImport = $this->sourceProvider->getData($query);
        foreach ($dataForImport as $data) {
            if (!empty($sourcePkKey)) {
                unset($data[$sourcePkKey]);
            }

            $operationResult = $this->targetProvider->save($data);
            $resultList[] = new OperationResult(
                $operationResult->hasError() ? $operationResult->getErrorMessage() : null,
                $data
            );
        }

        return new MigrateResult($query, $resultList);
    }
}