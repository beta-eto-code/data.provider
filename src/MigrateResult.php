<?php

namespace Data\Provider;

use Data\Provider\Interfaces\MigrateResultInterface;
use Data\Provider\Interfaces\OperationResultInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;

class MigrateResult implements MigrateResultInterface
{
    /**
     * @var QueryCriteriaInterface
     */
    private $query;
    /**
     * @var OperationResultInterface[]
     */
    private $resultList;

    public function __construct(
        QueryCriteriaInterface $query,
        array $resultList
    ) {
        $this->query = $query;
        $this->resultList = $resultList;
    }

    /**
     * @return QueryCriteriaInterface
     */
    public function getQuery(): QueryCriteriaInterface
    {
        return $this->query;
    }

    /**
     * @return array
     *
     * @psalm-return list<mixed>
     */
    public function getSourceData(): array
    {
        $result = [];
        foreach ($this->resultList as $operationResult) {
            foreach ($operationResult as $r) {
                $result[] = $r->getData();
            }
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function hasErrors(): bool
    {
        foreach ($this->resultList as $operationResult) {
            if ($operationResult->hasError(true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return OperationResultInterface[]
     */
    public function getErrors(): array
    {
        $result = [];
        foreach ($this->resultList as $operationResult) {
            foreach ($operationResult as $r) {
                if ($r->hasError()) {
                    $result[] = $r;
                }
            }
        }

        return $result;
    }

    /**
     * @return array
     *
     * @psalm-return list<mixed>
     */
    public function getUnimportedDataList(): array
    {
        $result = [];
        foreach ($this->resultList as $operationResult) {
            if ($operationResult->hasError()) {
                $result[] = $operationResult->getData();
            }
        }

        return $result;
    }

    /**
     * @return OperationResultInterface[]
     */
    public function getResultList(): array
    {
        return $this->resultList;
    }
}
