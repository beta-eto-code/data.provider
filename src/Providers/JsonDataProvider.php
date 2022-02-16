<?php

namespace Data\Provider\Providers;

use ArrayObject;
use Closure;
use DateTime;
use Throwable;

class JsonDataProvider extends BaseFileDataProvider
{
    /**
     * @var string
     */
    private $filePath;
    /**
     * @var Closure|null
     */
    private $dataSaveHandler;

    public function __construct(
        string $filePath,
        string $pkName = 'id',
        Closure $dataSaveHandler = null
    ) {
        parent::__construct($pkName);
        $this->filePath = $filePath;
        $this->dataSaveHandler = $dataSaveHandler;
    }

    /**
     * @return array
     */
    protected function readDataFromFile(): array
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
     * @param array $dataList
     * @return bool
     */
    protected function saveDataList(array $dataList): bool
    {
        foreach ($dataList as &$item) {
            $item = $this->normalizeArray($item);
        }
        unset($item);

        return file_put_contents(
            $this->filePath,
            json_encode($dataList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        ) !== false;
    }

    /**
     * @param array|ArrayObject $data
     * @return bool
     */
    protected function appendData($data): bool
    {
        $dataList = $this->readDataFromFile();
        $dataList[] = $data;

        return $this->saveDataList($dataList);
    }

    /**
     * @param array|ArrayObject $data
     *
     * @return (array|false|mixed|string)[]
     *
     * @psalm-return array<string, array|false|mixed|string>
     */
    private function normalizeArray($data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $name = str_replace(['[',']'], '', $key);
            if ($value instanceof DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            }

            $result[$name] = is_array($value) ? $this->normalizeArray($value) : $value;
        }

        return $result;
    }

    /**
     * @return Closure|null
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
