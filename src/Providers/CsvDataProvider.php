<?php

namespace Data\Provider\Providers;

use Closure;
use Data\Provider\Interfaces\DataProviderInterface;
use Data\Provider\Interfaces\OperationResultInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;
use Data\Provider\OperationResult;
use Data\Provider\QueryCriteria;

class CsvDataProvider implements DataProviderInterface
{
    /**
     * @var string
     */
    private $filePath;
    /**
     * @var string|Closure
     */
    private $pkKey;
    /**
     * @var string
     */
    private $separator;
    /**
     * @var string
     */
    private $enclusure;
    /**
     * @var string
     */
    private $escape;
    /**
     * @var string[]
     */
    private $headers;

    public function __construct(
        string $filePath,
        $pkKey,
        string $separator = ',',
        string $enclosure = '"',
        string $escape = "\\"
    ) {
        $this->filePath = $filePath;
        $this->pkKey = $pkKey;
        $this->separator = $separator;
        $this->enclusure = $enclosure;
        $this->escape = $escape;
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
     * @param string[] $defaultKeys
     * @return string[]
     */
    private function getHeaders(array $defaultKeys = []): array
    {
        if (!empty($this->headers)) {
            return $this->headers;
        }

        if (!file_exists($this->filePath)) {
            return $this->headers = $defaultKeys;
        }

        $file = fopen($this->filePath, 'r');
        $data = $this->getCsvRow($file);
        fclose($file);

        if (empty($data)) {
            return $this->headers = $defaultKeys;
        }

        $this->headers = $data;
    }

    /**
     * @param $file
     * @return array|bool
     */
    private function getCsvRow($file)
    {
        return fgetcsv($file, 0, $this->separator, $this->enclusure, $this->escape);
    }

    /**
     * @param array $headers
     * @param array $row
     * @return array
     */
    private function readItem(array $headers, array $row): array
    {
        $item = [];
        foreach ($row as $i => $value) {
            $key = $headers[$i] ?? null;
            if (empty($key)) {
                continue;
            }

            $item[$key] = $value;
        }

        ksort($item);

        return $item;
    }

    /**
     * @param QueryCriteriaInterface $query
     * @return array
     */
    protected function getDataInternal(QueryCriteriaInterface $query): array
    {
        $headers = $this->getHeaders();
        if (empty($headers)) {
            return [];
        }

        $index = 0;
        $result = [];
        $file = fopen($this->filePath, 'r');

        $limit = $query->getLimit();
        $offset = $query->getOffset();
        $criteriaList = $query->getCriteriaList();
        while ($row = $this->getCsvRow($file) !== false) {
            if ($index++ === 0) {
                continue;
            }

            if ($offset > 0) {
                $offset--;
                continue;
            }

            $item = $this->readItem($headers, $row);
            if (!empty($item)) {
                $isSuccess = true;
                foreach ($criteriaList as $compareRule) {
                    if (!$compareRule->assertWithData($item)) {
                        $isSuccess = false;
                        break;
                    }
                }

                if ($isSuccess) {
                    $result[] = $item;
                    if ($limit > 0 && count($result) >= $limit) {
                        break;
                    }
                }
            }
        }

        fclose($file);

        return $result;
    }

    /**
     * @return string
     */
    public function getSourceName(): string
    {
        return $this->filePath;
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
        return count($this->getData($query));
    }

    /**
     * @param array $data
     * @param mixed|null $pk
     * @return OperationResultInterface
     */
    public function save(array $data, $pk = null): OperationResultInterface
    {
        $headers = $this->getHeaders(array_keys($data));
        $dataForSave = [];
        if (empty($pk)) {
            $file = fopen($this->filePath, 'w+');
            foreach ($headers as $key) {
                $dataForSave[] = $data[$key] ?? null;
            }

            $isSuccess = (bool)fputcsv($file, $dataForSave, $this->separator, $this->enclusure, $this->escape);
            fclose($file);

            return $isSuccess ?
                new OperationResult() :
                new OperationResult('Ошибка сохранения данных', ['pk' => $pk, 'data' => $data]);
        }

        $isFind = false;
        $listData = $this->getData(new QueryCriteria());
        foreach ($listData as &$item) {
            if ((is_callable($this->pkKey) && ($this->pkKey)($item, $pk)) ||
                (!is_callable($this->pkKey) && $item[$this->pkKey] === $pk)) {
                foreach ($headers as $i => $key) {
                    if (!is_null($data[$key])) {
                        $isFind = true;
                        $item[$i] = $data[$key];
                    }
                }
                break;
            }
        }
        unset($item);

        if (!$isFind) {
            return new OperationResult('Элемент не найден', ['pk' => $pk, 'data' => $data]);
        }

        $file = fopen($this->filePath, 'w');
        fputcsv($file, $headers, $this->separator, $this->enclusure, $this->escape);
        foreach ($listData as $item) {
            fputcsv($file, $item, $this->separator, $this->enclusure, $this->escape);
            fclose($file);
        }

        return new OperationResult();
    }

    /**
     * @param mixed $pk
     * @return OperationResultInterface
     */
    public function remove($pk): OperationResultInterface
    {
        $headers = $this->getHeaders();
        if (empty($headers)) {
            return new OperationResult('Элемент не найден', ['pk' => $pk]);
        }

        $result = [];
        $isFind = false;
        $listData = $this->getData(new QueryCriteria());
        foreach ($listData as $item) {
            if ((is_callable($this->pkKey) && ($this->pkKey)($item, $pk)) ||
                (!is_callable($this->pkKey) && $item[$this->pkKey] === $pk)) {
                continue;
            }

            $result[] = $item;
        }

        if (!$isFind) {
            return new OperationResult('Элемент не найден', ['pk' => $pk]);
        }

        $file = fopen($this->filePath, 'w');
        fputcsv($file, $headers, $this->separator, $this->enclusure, $this->escape);
        foreach ($result as $item) {
            fputcsv($file, $item, $this->separator, $this->enclusure, $this->escape);
            fclose($file);
        }

        return new OperationResult();
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