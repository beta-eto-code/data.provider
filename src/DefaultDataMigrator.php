<?php

namespace Data\Provider;

use Data\Provider\Interfaces\CompareRuleInterface;
use Data\Provider\Interfaces\DataMigratorInterface;
use Data\Provider\Interfaces\DataProviderInterface;
use Data\Provider\Interfaces\MigrateResultInterface;
use Data\Provider\Interfaces\PkOperationResultInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;
use Exception;

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
     *
     * @return MigrateResult
     */
    public function runInsert(QueryCriteriaInterface $query): MigrateResultInterface
    {
        $dataForImport = $this->sourceProvider->getData($query);
        return new MigrateResult(
            $query,
            $this->insertData($dataForImport)
        );
    }

    /**
     * @param array $dataForImport
     *
     * @return OperationResult[]
     *
     * @psalm-return list<OperationResult>
     */
    private function insertData(array $dataForImport): array
    {
        $resultList = [];
        foreach ($dataForImport as $data) {
            $this->sourceProvider->clearPk($data);
            $operationResult = $this->targetProvider->save($data);
            $resultList[] = new OperationResult(
                $operationResult->hasError() ? $operationResult->getErrorMessage() : null,
                $data
            );
        }

        return $resultList;
    }

    /**
     * @param array $dataList
     * @param string|null $keyName
     * @return array
     */
    private function getDataForUpdate(array $dataList, string $keyName = null): array
    {
        $keyName = $keyName ?? $this->sourceProvider->getPkName();
        if (empty($keyName)) {
            return [];
        }

        $resultList = [];
        foreach ($dataList as $item) {
            $keyValue = $item[$keyName] ?? null;
            if (!empty($keyValue)) {
                $resultList[$keyValue] = $item;
            }
        }

        return $resultList;
    }

    /**
     * @param array $dataList
     * @param string|null $keyName
     * @return array
     */
    private function getDataForInsert(array $dataList, string $keyName = null): array
    {
        $keyName = $keyName ?? $this->sourceProvider->getPkName();
        if (empty($keyName)) {
            return $dataList;
        }

        $resultList = [];
        foreach ($dataList as $item) {
            if (empty($item[$keyName])) {
                $resultList[] = $item;
            }
        }

        return $resultList;
    }

    /**
     * @param PkOperationResultInterface $result
     * @param array $data
     * @return OperationResult
     */
    private function prepareResult(PkOperationResultInterface $result, array $data): OperationResult
    {
        $errorMessage = $result->getErrorMessage();

        return new OperationResult(
            !empty($errorMessage) ? $errorMessage : null,
            $data
        );
    }

    /**
     * @param QueryCriteria $query
     * @param callable|string|null $compareRule - key for compare value or closure
     * function(array $dataImport): QueryCriteriaInterface
     * @param bool $insertOnFailUpdate
     *
     * @return MigrateResult
     *
     * @throws Exception
     */
    public function runUpdate(
        QueryCriteria $query,
        $compareRule = null,
        bool $insertOnFailUpdate = false
    ): MigrateResultInterface {
        $compareRule = is_null($compareRule) ? $this->targetProvider->getPkName() : $compareRule;
        if (!is_string($compareRule) && !is_callable($compareRule)) {
            throw new Exception('invalid compare rule for update data');
        }

        $updateResultList = [];
        $dataForImport = $this->sourceProvider->getData($query);
        if (is_string($compareRule)) {
            $dataForInsert = $this->getDataForInsert($dataForImport, $compareRule);

            $targetPkName = $this->targetProvider->getPkName();
            if (!empty($targetPkName)) {
                foreach ($this->getDataForUpdate($dataForImport, $compareRule) as $pkValue => $item) {
                    $query = new QueryCriteria();
                    $query->addCriteria($targetPkName, CompareRuleInterface::EQUAL, $pkValue);
                    $updateResult = $this->targetProvider->save($item, $query);
                    if ($insertOnFailUpdate && $updateResult->hasError()) {
                        $dataForInsert[] = $item;
                    } else {
                        $updateResultList[] = $this->prepareResult($updateResult, (array)$item);
                    }
                }
            }

            $insertResultList = $this->insertData($dataForInsert);
            $resultList = array_merge($updateResultList, $insertResultList);

            return new MigrateResult($query, $resultList);
        }


        $dataForInsert = [];
        foreach ($dataForImport as $item) {
            $query = $compareRule($item);
            if ($query instanceof QueryCriteriaInterface) {
                $updateResult = $this->targetProvider->save($item, $query);
                if ($insertOnFailUpdate && $updateResult->hasError()) {
                    $dataForInsert[] = $item;
                } else {
                    $updateResultList[] = $this->prepareResult($updateResult, (array)$item);
                }
            }
        }

        $insertResultList = $this->insertData($dataForInsert);
        $resultList = array_merge($updateResultList, $insertResultList);

        return new MigrateResult($query, $resultList);
    }
}
