<?php

namespace Data\Provider\Providers;

use Closure;
use Data\Provider\Interfaces\OperationResultInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;
use Data\Provider\OperationResult;
use Data\Provider\QueryCriteria;

abstract class BaseFileDataProvider extends BaseDataProvider
{
    /**
     * @var int
     */
    protected $lastPk;

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
     * @param array $data
     * @return bool
     */
    abstract protected function appendData(array $data): bool;

    /**
     * @param QueryCriteriaInterface $query
     * @return array
     */
    protected function getDataInternal(QueryCriteriaInterface $query): array
    {
        $dataList = $query->getOrderBy()->sortData(
            $this->readDataFromFile()
        );

        return $query->createDataChecker()->filterDataList($dataList);
    }

    /**
     * @param QueryCriteriaInterface $query
     * @return OperationResultInterface
     */
    public function remove(QueryCriteriaInterface $query): OperationResultInterface
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
     * @return OperationResultInterface
     */
    protected function saveInternal(array $data, QueryCriteriaInterface $query = null): OperationResultInterface
    {
        $errorMessage = 'Ошибка сохранения данных';
        if (empty($query)) {
            return $this->appendData($data) ?
                new OperationResult(null, ['query' => $query, 'data' => $data]) :
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
     * @param QueryCriteriaInterface $query
     * @return int
     */
    public function getDataCount(QueryCriteriaInterface $query): int
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

        if (!is_null($this->lastPk)) {
            return (int)$this->lastPk;
        }

        $query = new QueryCriteria();
        $query->setLimit(1);
        $query->setOrderBy($this->pkName, false);
        $result = $this->getData($query);
        if (empty($result)) {
            return 0;
        }

        $lastItem = current($result);

        return $this->lastPk = (int)($lastItem[$this->pkName] ?? 0);
    }

    /**
     * @param $data
     * @param int|null $pk
     */
    protected function setPkForData(&$data, int $pk = null)
    {
        if (!$this->isValidPk()) {
            return;
        }

        $pk = $data[$this->pkName] ?? null;
        if (!empty($pk)) {
            return;
        }

        $lastPk = $this->getLastPk();
        if ($pk > 0) {
            $data[$this->pkName] = $pk;
            if ($pk > $lastPk) {
                $this->lastPk = $pk;
            }
        } else {
            $this->lastPk = $lastPk + 1;
            $data[$this->pkName] = $this->lastPk;
        }
    }
}