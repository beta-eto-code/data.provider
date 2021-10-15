<?php

namespace Data\Provider\Providers;

use ArrayObject;
use Data\Provider\Providers\Traits\OperationStorageTrait;

class ArrayDataProvider extends BaseFileDataProvider
{
    use OperationStorageTrait;

    /**
     * @var array
     */
    private $dataList;

    public function __construct(array $dataList, string $pkName = null)
    {
        $this->dataList = $dataList;
        parent::__construct($pkName);
    }

    /**
     * @return array
     */
    protected function readDataFromFile(): array
    {
        return $this->dataList;
    }

    /**
     * @param array $dataList
     * @return bool
     */
    protected function saveDataList(array $dataList): bool
    {
        $this->dataList = $dataList;
        return true;
    }

    /**
     * @param array|ArrayObject $data
     * @return bool
     */
    protected function appendData($data): bool
    {
        $this->dataList[] = $data;
        return true;
    }

    /**
     * @return string
     */
    public function getSourceName(): string
    {
        return 'internal array';
    }

}