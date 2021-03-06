<?php

namespace Data\Provider\Providers;

class CsvDataProvider extends BaseFileDataProvider
{
    /**
     * @var string
     */
    private $filePath;
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
        string $pkName = null,
        string $separator = ',',
        string $enclosure = '"',
        string $escape = "\\"
    ) {
        parent::__construct($pkName);

        $this->filePath = $filePath;
        $this->separator = $separator;
        $this->enclusure = $enclosure;
        $this->escape = $escape;
        $this->headers = [];
    }

    /**
     * @return string[]
     */
    private function getHeaders(): array
    {
        if (!empty($this->headers)) {
            return $this->headers;
        }

        if (!file_exists($this->filePath)) {
            return [];
        }

        $file = fopen($this->filePath, 'r');
        $data = $this->getCsvRow($file);
        fclose($file);

        if (empty($data)) {
            return [];
        }

        if (!empty($this->pkName) && !in_array($this->pkName, $data)) {
            $data[] = $this->pkName;
        }

        return $this->headers = $data;
    }

    /**
     * @param resource $file
     *
     * @return array|false (null|string)[]|false|null
     *
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
     * @return array[]
     *
     * @psalm-return list<array>
     */
    protected function readDataFromFile(): array
    {
        $headers = $this->getHeaders();
        if (empty($headers)) {
            return [];
        }

        $file = fopen($this->filePath, 'r');

        $index = 0;
        $result = [];
        while ($row = $this->getCsvRow($file)) {
            if ($index++ === 0) {
                continue;
            }

            $item = $this->readItem($headers, $row);
            if (!empty($item)) {
                $result[] = $item;
            }
        }

        fclose($file);

        return $result;
    }

    /**
     * @param array $dataList
     *
     * @return true
     */
    protected function saveDataList(array $dataList): bool
    {
        $headers = $this->getHeaders();
        $file = fopen($this->filePath, 'w');
        $this->saveItem($file, $headers);
        foreach ($dataList as $item) {
            $this->saveItem($file, $this->prepareItemForSave($headers, $item));
        }

        fclose($file);

        return true;
    }

    /**
     * @param array $data
     * @return bool
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    protected function appendData($data): bool
    {
        $headers = $this->getHeaders();
        $file = fopen($this->filePath, 'a');
        if (empty($headers)) {
            $headers = array_keys($data);
            if ($this->isValidPk() && !in_array($this->pkName, $headers)) {
                $headers[] = $this->pkName;
            }

            $this->saveItem($file, $headers);
        }


        $dataForSave = $this->prepareItemForSave($headers, $data);
        $isSuccess = (bool)$this->saveItem($file, $dataForSave);
        fclose($file);

        return $isSuccess;
    }

    /**
     * @return string
     */
    public function getSourceName(): string
    {
        return $this->filePath;
    }

    /**
     * @param array $headers
     * @param array $data
     * @return array
     */
    private function prepareItemForSave(array $headers, array $data): array
    {
        $item = [];
        foreach ($headers as $i => $key) {
            if (!is_null($data[$key])) {
                $item[$i] = $data[$key];
            }
        }

        return $item;
    }

    /**
     * @param resource $file
     * @param array $data
     *
     * @return false|int
     */
    private function saveItem($file, array $data)
    {
        return fputcsv($file, $data, $this->separator, $this->enclusure, $this->escape);
    }
}
