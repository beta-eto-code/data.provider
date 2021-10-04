<?php

namespace Data\Provider\Providers;

use Closure;
use Data\Provider\Interfaces\DataProviderInterface;
use Data\Provider\Interfaces\OperationResultInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;
use Data\Provider\OperationResult;
use DateTime;
use Throwable;

class JsonDataProvider implements DataProviderInterface
{
    /**
     * @var string
     */
    private $filePath;
    /**
     * @var string
     */
    private $pkName;
    /**
     * @var Closure|null
     */
    private $dataSaveHandler;

    public function __construct(
        string $filePath,
        string $pkName = 'id',
        Closure $dataSaveHandler = null
    )
    {
        $this->filePath = $filePath;
        $this->pkName = $pkName;
        $this->dataSaveHandler = $dataSaveHandler;
    }

    /**
     * @return array
     */
    private function getDataFromFile(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $data = file_get_contents($this->filePath);
        if (empty($data)) {
            return [];
        }

        try {
            return json_decode($data, true) ?? [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @param QueryCriteriaInterface $query
     * @return array
     * @throws Exception
     */
    public function getData(QueryCriteriaInterface $query): array
    {
        $data = $this->getDataInternal($query);
        foreach ($query->getJoinList() as $joinRule) {
            $joinRule->loadTo($data);
            $joinRule->filterData($data);
        }

        return $data;
    }

    /**
     * @param QueryCriteriaInterface $query
     * @return array
     */
    protected function getDataInternal(QueryCriteriaInterface $query): array
    {
        $resultList = [];
        $dataList = $this->getDataFromFile();
        if (empty($dataList)) {
            return [];
        }

        $dataList = $query->getOrderBy()->sortData($dataList);
        $criteriaList = $query->getCriteriaList();
        $limit = $query->getLimit();
        $offset = $query->getOffset();
        foreach ($dataList as $data) {
            if ($offset > 0) {
                $offset--;
                continue;
            }

            $isSuccess = true;
            foreach ($criteriaList as $compareRule) {
                if (!$compareRule->assertWithData($data)) {
                    $isSuccess = false;
                    break;
                }
            }

            if ($isSuccess) {
                $resultList[] = $data;
                if ($limit > 0 && count($resultList) >= $limit) {
                    break;
                }
            }
        }

        return $resultList;
    }

    /**
     * @param QueryCriteriaInterface $query
     * @return int
     */
    public function getDataCount(QueryCriteriaInterface $query): int
    {
        $count = 0;
        $dataList = $this->getDataFromFile();
        if (empty($dataList)) {
            return 0;
        }

        $criteriaList = $query->getCriteriaList();
        foreach ($dataList as $data) {
            $isSuccess = true;
            foreach ($criteriaList as $compareRule) {
                if (!$compareRule->assertWithData($data)) {
                    $isSuccess = false;
                    break;
                }
            }

            if ($isSuccess) {
                $count++;
            }
        }

        return $count;
    }

    public function normalizePk($pk)
    {
        if (is_int($pk)) {
            return $pk;
        }

        if (is_object($pk) || is_array($pk)) {
            return md5(serialize($pk));
        }

        return (string)$pk;
    }

    /**
     * @param array $data
     * @return array
     */
    private function normalizeArray(array $data): array
    {
        $result = [];
        foreach($data as $key => $value) {
            $name = str_replace(['[',']'], '', $key);
            if ($value instanceof DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            }

            $result[$name] = is_array($value) ? $this->normalizeArray($value) : $value;
        }

        return $result;
    }

    public function save(array $data, $pk = null): OperationResultInterface
    {
        if (isset($data[$this->pkName])) {
            unset($data[$this->pkName]);
        }

        $dataList = $this->getDataFromFile();
        if (!empty($pk)) {
            $normalizedPk = $this->normalizePk($pk);
        } else {
            $normalizedPk = count($dataList)+1;
        }

        $data[$this->pkName] = $normalizedPk;
        $dataFromStorage = $dataList[$normalizedPk] ?? [];
        $dataList[$normalizedPk] = $this->normalizeArray(array_merge($dataFromStorage, $data));
        $isSuccess = file_put_contents($this->filePath, json_encode($dataList,  JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) !== false;
        if (!$isSuccess) {
            return new OperationResult('Ошибка сохранения данных', $data);
        }

        $result = $dataList[$normalizedPk];
        $result['pk'] = $normalizedPk;

        if (!empty($this->pkName)) {
            $result[$this->pkName] = $normalizedPk;
        }

        return new OperationResult(null, $result);
    }

    public function remove($pk): OperationResultInterface
    {
        $dataList = $this->getDataFromFile();
        $normalizedPk = $this->normalizePk($pk);
        if (!isset($dataList[$normalizedPk])) {
            return new OperationResult('Запись не найдена', $pk);
        }

        unset($dataList[$normalizedPk]);

        return new OperationResult(null, $pk);
    }

    /**
     * @return Closure|null - function(array $data): array
     */
    public function getDataHandler(): ?Closure
    {
        return $this->dataSaveHandler;
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

    /**
     * @return string
     */
    public function getSourceName(): string
    {
        return $this->filePath;
    }
}