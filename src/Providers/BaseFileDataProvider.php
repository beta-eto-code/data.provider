<?php

namespace Data\Provider\Providers;

use ArrayObject;
use Closure;
use Data\Provider\Interfaces\OperationResultInterface;
use Data\Provider\Interfaces\PkOperationResultInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;
use Data\Provider\OperationResult;
use Data\Provider\Providers\Traits\OperationStorageTrait;
use Data\Provider\QueryCriteria;
use Data\Provider\TransactionOperationResult;
use EmptyIterator;
use Generator;
use Iterator;

abstract class BaseFileDataProvider extends BaseDataProvider
{
    use OperationStorageTrait;

    /**
     * @var int
     */
    protected $lastPk = 0;

    /**
     * @var bool
     */
    private $transactionMode;

    /**
     * @return array
     */
    abstract protected function readDataFromFile(): array;

    /**
     * @param array $dataList
     * @return bool
     */
    abstract protected function saveDataList(array $dataList): bool;

    /**
     * @param array|ArrayObject $data
     * @return bool
     */
    abstract protected function appendData($data): bool;

    public function save(&$data, QueryCriteriaInterface $query = null): PkOperationResultInterface
    {
        if ($this->transactionMode) {
            $transactionResult = new TransactionOperationResult();
            $this->addOperation(function () use (&$data, $query, $transactionResult) {
                $result = parent::save($data, $query);
                $transactionResult->setResult($result);

                return $result;
            });

            return $transactionResult;
        }

        return parent::save($data, $query);
    }

    /**
     * @param QueryCriteriaInterface|null $query
     *
     * @return Generator
     *
     * @psalm-return Generator<int, array|mixed, mixed, EmptyIterator>
     */
    protected function getInternalIterator(QueryCriteriaInterface $query = null): Iterator
    {
        if ($query === null) {
            foreach ($this->readDataFromFile() as $dataItem) {
                yield $dataItem;
            }

            return new EmptyIterator();
        }

        $dataList = $query->getOrderBy()->sortData(
            $this->readDataFromFile()
        );

        $dataChecker = $query->createDataChecker();
        foreach ($dataList as $dataItem) {
            if ($dataChecker->failByLimit()) {
                break;
            }

            if ($dataChecker->assertDataByCriteria($dataItem)) {
                yield $dataItem;
            }
        }

        return new EmptyIterator();
    }

    /**
     * @param QueryCriteriaInterface $query
     *
     * @return OperationResult|TransactionOperationResult
     */
    public function remove(QueryCriteriaInterface $query): OperationResultInterface
    {
        if ($this->transactionMode) {
            $transactionResult = new TransactionOperationResult();
            $this->addOperation(function () use ($query, $transactionResult) {
                $result = $this->internalRemove($query);
                $transactionResult->setResult($result);

                return $result;
            });

            return $transactionResult;
        }

        return $this->internalRemove($query);
    }

    /**
     * @param QueryCriteriaInterface $query
     */
    private function internalRemove(QueryCriteriaInterface $query): OperationResult
    {
        $errorMessage = 'Данные для удаления не найдены';
        $listData = $this->readDataFromFile();
        if (empty($listData)) {
            return new OperationResult($errorMessage, ['query' => $query]);
        }

        $result = [];
        $dataChecker = $query->createDataChecker();
        foreach ($listData as $item) {
            if ($dataChecker->failByLimit()) {
                break;
            }

            if (!$dataChecker->assertDataByCriteria($item)) {
                $result[] = $item;
            }
        }

        if (!$dataChecker->successCount()) {
            return new OperationResult($errorMessage, ['query' => $query]);
        }

        $this->saveDataList($result);

        return new OperationResult(null, ['query' => $query]);
    }

    /**
     * @param array $data
     * @param QueryCriteriaInterface|null $query
     *
     * @return OperationResult
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    protected function saveInternal(&$data, QueryCriteriaInterface $query = null): PkOperationResultInterface
    {
        $errorMessage = 'Ошибка сохранения данных';
        if (empty($query)) {
            $this->setPkForData($data);
            $pk = $this->getPkValue($data);

            /**
             * @psalm-suppress ReferenceConstraintViolation
             */
            return $this->appendData($data) ?
                new OperationResult(null, ['query' => $query, 'data' => $data], $pk) :
                new OperationResult($errorMessage, ['query' => $query, 'data' => $data]);
        }

        $dataChecker = $query->createDataChecker();
        $dataList = $this->readDataFromFile();
        foreach ($dataList as &$item) {
            if ($dataChecker->failByLimit()) {
                break;
            }

            if ($dataChecker->assertDataByCriteria($item)) {
                $item = array_merge($item, $data);
            }
        }
        unset($item);

        if (!$dataChecker->successCount()) {
            return new OperationResult($errorMessage, ['query' => $query, 'data' => $data]);
        }

        return $this->saveDataList($dataList) ?
            new OperationResult(null, ['query' => $query, 'data' => $data]) :
            new OperationResult($errorMessage, ['query' => $query, 'data' => $data]);
    }

    /**
     * @param QueryCriteriaInterface|null $query
     *
     * @return int
     *
     * @psalm-return 0|positive-int
     */
    public function getDataCount(QueryCriteriaInterface $query = null): int
    {
        return count($this->getData($query));
    }

    /**
     * @return Closure|null
     */
    public function getDataHandler(): ?Closure
    {
        return null;
    }

    /**
     * @return bool
     */
    protected function isValidPk(): bool
    {
        return is_string($this->pkName) && !empty($this->pkName);
    }

    /**
     * @return int
     */
    protected function getLastPk(): int
    {
        if (!$this->isValidPk()) {
            return 0;
        }

        if (!empty($this->lastPk)) {
            return $this->lastPk;
        }

        $query = new QueryCriteria();
        $query->setLimit(1);
        if (!empty($this->pkName)) {
            $query->setOrderBy($this->pkName, false);
        }

        $result = $this->getData($query);
        if (empty($result)) {
            return 0;
        }

        $lastItem = current($result);

        return $this->lastPk = !empty($this->pkName) ? (int)($lastItem[$this->pkName] ?? 0) : 0;
    }

    /**
     * @param ArrayObject|array $data
     * @param int|null $pk
     *
     * @return void
     */
    protected function setPkForData(&$data, int $pk = null)
    {
        if (!$this->isValidPk()) {
            return;
        }

        $pk = !empty($this->pkName) ? ($data[$this->pkName] ?? null) : null;
        if (!empty($pk)) {
            return;
        }

        $lastPk = $this->getLastPk();
        if ($pk > 0) {
            if (!empty($this->pkName)) {
                $data[$this->pkName] = $pk;
            }

            if ($pk > $lastPk) {
                $this->lastPk = $pk;
            }
        } else {
            $this->lastPk = $lastPk + 1;
            if (!empty($this->pkName)) {
                $data[$this->pkName] = $this->lastPk;
            }
        }
    }

    /**
     * @return bool
     */
    public function startTransaction(): bool
    {
        $this->transactionMode = true;
        return true;
    }

    /**
     * @return bool
     */
    public function commitTransaction(): bool
    {
        $this->transactionMode = false;
        $this->executeOperations();

        return true;
    }

    /**
     * @return bool
     */
    public function rollbackTransaction(): bool
    {
        $this->transactionMode = false;
        $this->clearOperations();

        return true;
    }
}
