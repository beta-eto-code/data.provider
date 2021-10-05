<?php

namespace Data\Provider\Interfaces;

interface DataCheckerInterface
{
    /**
     * @param array $data
     * @return bool
     */
    public function assertDataByCriteria(array $data): bool;

    /**
     * @param array $dataList
     * @return array
     */
    public function filterDataList(array $dataList): array;

    /**
     * @return bool
     */
    public function failByLimit(): bool;

    /**
     * @return int
     */
    public function successCount(): int;
}