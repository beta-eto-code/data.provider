<?php

namespace Data\Provider\Providers;

use Closure;
use Data\Provider\Interfaces\OperationResultInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;
use Data\Provider\OperationResult;

class ClosureDataProvider extends BaseDataProvider
{
    /**
     * @var int
     */
    private $defaultLimit;
    /**
     * @var Closure
     */
    private $itemHandler;

    /**
     * @param int $defaultLimit
     * @param Closure $itemHandler - function(QueryCriteriaInterface $query): array
     */
    public function __construct(int $defaultLimit, Closure $itemHandler)
    {
        $this->defaultLimit = $defaultLimit;
        $this->itemHandler = $itemHandler;
    }

    /**
     * @param QueryCriteriaInterface $query
     * @return array
     */
    protected function getDataInternal(QueryCriteriaInterface $query): array
    {
        $result = [];
        $limit = $query->getLimit() > 0 ? $query->getLimit() : $this->defaultLimit;
        while ($limit-- > 0) {
            $result[] = ($this->itemHandler)($query);
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getSourceName(): string
    {
        return 'closure';
    }

    /**
     * @return Closure|null
     */
    public function getDataHandler(): ?Closure
    {
        return null;
    }

    /**
     * @param QueryCriteriaInterface $query
     * @return int
     */
    public function getDataCount(QueryCriteriaInterface $query): int
    {
        return $query->getLimit() > 0 ? $query->getLimit() : $this->defaultLimit;
    }

    /**
     * @param array $data
     * @param mixed|null $pk
     * @return OperationResultInterface
     */
    protected function saveInternal(array $data, $pk = null): OperationResultInterface
    {
        return new OperationResult('Not implemented', ['data' => $data, 'pk' => $pk]);
    }

    /**
     * @param mixed $pk
     * @return OperationResultInterface
     */
    public function remove($pk): OperationResultInterface
    {
        return new OperationResult('Not implemented', ['pk' => $pk]);
    }

    /**
     * @return bool
     */
    public function startTransaction(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function commitTransaction(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function rollbackTransaction(): bool
    {
        return false;
    }
}