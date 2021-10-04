<?php

namespace Data\Provider;

use Closure;
use Data\Provider\Interfaces\CompareRuleInterface;
use Data\Provider\Interfaces\DataProviderInterface;
use Data\Provider\Interfaces\OperationResultInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;

class DataProviderBxDataManager implements DataProviderInterface
{
    /**
     * @var string
     */
    private $dataManagerClass;
    /**
     * @var Closure|null
     */
    private $dataHandlerForSave;
    /**
     * @var Closure|null
     */
    private $dataHandlerForRead;

    public function __construct(
        string $dataManagerClass,
        ?Closure $dataHandlerForSave = null,
        ?Closure $dataHandlerForRead = null
    )
    {
        $this->dataManagerClass = $dataManagerClass;
        $this->dataHandlerForSave = $dataHandlerForSave;
        $this->dataHandlerForRead = $dataHandlerForRead;
    }

    public function getDataHandler(): ?Closure
    {
        return $this->dataHandler;
    }

    private function buildParams(QueryCriteriaInterface $query): array
    {
        $params = [];
        $select = $query->getSelect();
        if (!empty($select)) {
            $params['select'] = $select;
        }

        $filter = [];
        foreach ($query->getCriteriaList() as $criteria) {
            $operation = $criteria->getOperation();
            $key = $criteria->getKey();

            switch ($operation) {
                case CompareRuleInterface::LIKE:
                    $key = "%{$key}";
                    break;
                case CompareRuleInterface::NOT_LIKE:
                    $key = "!%{$key}";
                    break;
                case CompareRuleInterface::IN:
                    $key = "={$key}";
                    break;
                case CompareRuleInterface::NOT_IN:
                    $key = "!{$key}";
                    break;
                default:
                    $key = "{$operation}{$key}";
            }

            $filter[$key] = $criteria->getCompareValue();
        }

        if (!empty($filter)) {
            $params['filter'] = $filter;
        }

        $sort = $query->getOrderBy()->getOrderData();
        if (!empty($sort)) {
            $params['sort'] = $sort;
        }

        $group = $query->getGroup();
        if (!empty($group)) {
            $params['group'] = $group;
        }

        return $params;
    }

    /**
     * @param QueryCriteriaInterface $query
     * @return array
     */
    public function getData(QueryCriteriaInterface $query): array
    {
        $params = $this->buildParams($query);
        return $this->dataManagerClass::getList($params)->fetchAll();
    }

    public function getDataCount(QueryCriteriaInterface $query): int
    {
        $params = $this->buildParams($query);
        $params['total_count'] = true;

        return $this->dataManagerClass::getList($params)->getCount();
    }

    /**
     * @param array $data
     * @param null $pk
     * @return OperationResultInterface
     */
    public function save(array $data, $pk = null): OperationResultInterface
    {
        if ($this->dataHandlerForSave !== null && is_callable($this->dataHandlerForSave)) {
            $data = ($this->dataHandlerForSave)($data);
        }
        
        if (!empty($pk)) {
            $result = $this->dataManagerClass::update($pk, $data);
            return new OperationResult(
                implode(', ', $result->getErrorMessages()),
                $data
            );
        }

        $result = $this->dataManagerClass::add($data);

        return new OperationResult(
            implode(', ', $result->getErrorMessages()),
            $data
        );
    }

    /**
     * @param mixed $pk
     * @return OperationResultInterface
     */
    public function remove($pk): OperationResultInterface
    {
        $result = $this->dataManagerClass::delete($pk);

        return new OperationResult(
            implode(', ', $result->getErrorMessages()),
            $pk
        );
    }

    /**
     * @return bool
     */
    public function startTransaction(): bool
    {
        \Bitrix\Main\Application::getConnection()->startTransaction();
        return true;
    }

    /**
     * @return bool
     */
    public function commitTransaction(): bool
    {
        \Bitrix\Main\Application::getConnection()->commitTransaction();
        return true;
    }

    /**
     * @return bool
     */
    public function rollbackTransaction(): bool
    {
        \Bitrix\Main\Application::getConnection()->rollbackTransaction();
        return true;
    }

    public function getSourceName(): string
    {
        return '';
    }
}