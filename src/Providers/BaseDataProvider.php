<?php

namespace Data\Provider\Providers;

use ArrayObject;
use Closure;
use Data\Provider\Interfaces\CompareRuleInterface;
use Data\Provider\Interfaces\DataProviderInterface;
use Data\Provider\Interfaces\PkOperationResultInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;
use Data\Provider\Interfaces\SqlRelationProviderInterface;
use Data\Provider\QueryCriteria;
use EmptyIterator;
use Iterator;

abstract class BaseDataProvider implements DataProviderInterface
{
    /**
     * @var callable|null
     */
    protected $mapperForRead;

    /**
     * @var callable|null
     */
    protected $mapperForSave;

    /**
     * @var string|null
     */
    protected $pkName;

    public function __construct(string $pkName = null)
    {
        $this->pkName = $pkName;
    }

    /**
     * @param QueryCriteriaInterface|null $query
     * @return Iterator
     */
    abstract protected function getInternalIterator(QueryCriteriaInterface $query = null): Iterator;

    /**
     * @param array|ArrayObject $data
     * @param QueryCriteriaInterface|null $query
     * @return PkOperationResultInterface
     */
    abstract protected function saveInternal(
        &$data,
        QueryCriteriaInterface $query = null
    ): PkOperationResultInterface;

    /**
     * @param callable $mapper - function(array $data): array
     */
    public function setMapperForRead(callable $mapper)
    {
        $this->mapperForRead = $mapper;
    }

    /**
     * @param callable $mapper - function(array $data): array
     */
    public function setMapperForSave(callable $mapper)
    {
        $this->mapperForSave = $mapper;
    }

    /**
     * @param QueryCriteriaInterface $query
     * @param array $dataItem
     * @return array
     */
    private function applySelect(QueryCriteriaInterface $query, array $dataItem): array
    {
        if (empty($query->getSelect())) {
            return $dataItem;
        }

        $result = [];
        foreach ($query->getSelect() as $k => $v) {
            $key = is_numeric($k) ? $v : $k;
            $result[$key] = $dataItem[$key] ?? null;
        }

        return $result;
    }

    /**
     * @param QueryCriteriaInterface $query
     *
     * @return \Generator
     *
     * @psalm-return \Generator<int, array|mixed, mixed, EmptyIterator>
     */
    public function getIterator(QueryCriteriaInterface $query): Iterator
    {
        foreach ($this->getInternalIterator($query) as $item) {
            $item = $this->applySelect($query, $item);
            if (is_callable($this->mapperForRead)) {
                $item = ($this->mapperForRead)($item);
            }

            $countJoin = 0;
            foreach ($query->getJoinList() as $joinRule) {
                $joinDataProvider = $joinRule->getDataProvider();
                if (
                    $this instanceof SqlRelationProviderInterface &&
                    $joinDataProvider instanceof SqlRelationProviderInterface
                ) {
                    continue;
                }

                foreach ($joinRule->processJoinToItem($item) as $resultItem) {
                    $countJoin++;
                    if ($joinRule->assertItem($resultItem)) {
                        yield $resultItem;
                    }
                }
            }

            if ($countJoin === 0) {
                yield $item;
            }
        }

        return new EmptyIterator();
    }

    /**
     * @param QueryCriteriaInterface|null $query
     * @return array
     */
    public function getData(QueryCriteriaInterface $query = null): array
    {
        $query = $query ?? new QueryCriteria();
        $dataList = [];
        foreach ($this->getInternalIterator($query) as $dataItem) {
            $dataList[] = is_callable($this->mapperForRead) ? ($this->mapperForRead)($dataItem) : $dataItem;
        }

        foreach ($query->getJoinList() as $joinRule) {
            $joinDataProvider = $joinRule->getDataProvider();
            if (
                $this instanceof SqlRelationProviderInterface &&
                $joinDataProvider instanceof SqlRelationProviderInterface
            ) {
                continue;
            }

            $joinRule->loadTo($dataList);
            $joinRule->filterData($dataList);
        }

        return $dataList;
    }

    /**
     * @param array|ArrayObject $data
     * @param QueryCriteriaInterface|null $query
     * @return PkOperationResultInterface
     */
    public function save(&$data, QueryCriteriaInterface $query = null): PkOperationResultInterface
    {
        if (is_callable($this->mapperForSave)) {
            $data = ($this->mapperForSave)($data);
        }

        return $this->saveInternal($data, $query);
    }

    /**
     * @return string|null
     */
    public function getPkName(): ?string
    {
        return $this->pkName;
    }

    /**
     * @param array|ArrayObject $data
     * @return void
     */
    public function clearPk(&$data)
    {
        if (!empty($this->pkName)) {
            unset($data[$this->pkName]);
        }
    }

    /**
     * @param array|ArrayObject $data
     * @return mixed
     */
    public function getPkValue($data)
    {
        if (empty($this->pkName)) {
            return null;
        }

        return $data[$this->pkName] ?? null;
    }

    /**
     * @param array|ArrayObject $data
     *
     * @return QueryCriteria|null
     */
    public function createPkQuery($data): ?QueryCriteriaInterface
    {
        $pkName = $this->getPkName();
        if (empty($pkName)) {
            return null;
        }

        $value = $this->getPkValue($data);
        if (empty($value)) {
            return null;
        }

        $query = new QueryCriteria();
        $query->addCriteria($pkName, CompareRuleInterface::EQUAL, $value);

        return $query;
    }
}
