<?php

namespace Data\Provider\Providers;

use Bitrix\Crm\ConfigChecker\Iterator;
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
     * @param QueryCriteriaInterface|null $query
     * @return \Iterator
     */
    protected function getInternalIterator(QueryCriteriaInterface $query = null): \Iterator
    {
        $limit = empty($query) ?
            $this->defaultLimit :
            ($query->getLimit() > 0 ? $query->getLimit() : $this->defaultLimit);

        while ($limit-- > 0) {
            yield ($this->itemHandler)($query);
        }

        return new \EmptyIterator();
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
     * @param QueryCriteriaInterface|null $query
     * @return int
     */
    public function getDataCount(QueryCriteriaInterface $query = null): int
    {
        if (empty($query)) {
            return $this->defaultLimit;
        }

        return $query->getLimit() > 0 ? $query->getLimit() : $this->defaultLimit;
    }

    /**
     * @param array|\ArrayObject $data
     * @param QueryCriteriaInterface|null $query
     * @return OperationResultInterface
     */
    protected function saveInternal(&$data, QueryCriteriaInterface $query = null): OperationResultInterface
    {
        return new OperationResult('Not implemented', ['data' => $data, 'query' => $query]);
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