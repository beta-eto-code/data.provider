<?php

namespace Data\Provider\Providers;

use ArrayObject;
use Closure;
use Data\Provider\Interfaces\OperationResultInterface;
use Data\Provider\Interfaces\PkOperationResultInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;
use Data\Provider\OperationResult;
use Generator;

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
        parent::__construct();
    }

    /**
     * @param QueryCriteriaInterface|null $query
     *
     * @return Generator
     *
     * @psalm-return Generator<int, mixed, mixed, \EmptyIterator>
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
     *
     * @psalm-return 'closure'
     */
    public function getSourceName(): string
    {
        return 'closure';
    }

    /**
     * @return null
     */
    public function getDataHandler()
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
     * @param array|ArrayObject $data
     * @param QueryCriteriaInterface|null $query
     *
     * @return OperationResult
     */
    protected function saveInternal(&$data, QueryCriteriaInterface $query = null): PkOperationResultInterface
    {
        return new OperationResult('Not implemented', ['data' => $data, 'query' => $query]);
    }

    /**
     * @param QueryCriteriaInterface $query
     * @return OperationResultInterface
     */
    public function remove(QueryCriteriaInterface $query): OperationResultInterface
    {
        return new OperationResult('Not implemented', ['query' => $query]);
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
