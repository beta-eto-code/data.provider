<?php

namespace Data\Provider\Providers;

use Closure;
use Data\Provider\Interfaces\DataProviderInterface;
use Data\Provider\Interfaces\OperationResultInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;
use Data\Provider\Interfaces\SqlRelationProviderInterface;

abstract class BaseDataProvider implements DataProviderInterface
{
    /**
     * @var Closure|null
     */
    protected $mapperForRead;

    /**
     * @var Closure|null
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
     * @param QueryCriteriaInterface $query
     * @return array
     */
    abstract protected function getDataInternal(QueryCriteriaInterface $query): array;

    /**
     * @param QueryCriteriaInterface $query
     * @return array
     */
    abstract protected function saveInternal(
        array $data,
        QueryCriteriaInterface $query = null
    ): OperationResultInterface;

    /**
     * @param Closure $mapper - function(array $data): array
     */
    public function setMapperForRead(Closure $mapper)
    {
        $this->mapperForRead = $mapper;
    }

    /**
     * @param Closure $mapper - function(array $data): array
     */
    public function setMapperForSave(Closure $mapper)
    {
        $this->mapperForSave = $mapper;
    }

    /**
     * @param QueryCriteriaInterface $query
     * @return array
     */
    final public function getData(QueryCriteriaInterface $query): array
    {
        $data = $this->getDataInternal($query);
        if (is_callable($this->mapperForRead)) {
            foreach ($data as $k => $dataItem) {
                $data[$k] = ($this->mapperForRead)($dataItem);
            }
        }

        foreach ($query->getJoinList() as $joinRule) {
            $joinDataProvider = $joinRule->getDataProvider();
            if ($this instanceof SqlRelationProviderInterface &&
                $joinDataProvider instanceof SqlRelationProviderInterface
            ) {
                continue;
            }

            $joinRule->loadTo($data);
            $joinRule->filterData($data);
        }

        return $data;
    }

    /**
     * @param array $data
     * @param QueryCriteriaInterface|null $query
     * @return OperationResultInterface
     */
    final public function save(array $data, QueryCriteriaInterface $query = null): OperationResultInterface
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
     * @param array $data
     * @return void
     */
    public function clearPk(array &$data)
    {
        if (!empty($this->pkName)) {
            unset($data[$this->pkName]);
        }
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function getPkValue(array $data)
    {
        if (empty($this->pkName)) {
            return null;
        }

        return $data[$this->pkName] ?? null;
    }
}