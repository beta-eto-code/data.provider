<?php

namespace Data\Provider\Interfaces;

interface OrderRuleInterface
{
    /**
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * @param string $key
     * @param bool $isAscending
     * @return mixed
     */
    public function setOrderByKey(string $key, bool $isAscending = true, ?string $alias = null);

    /**
     * @return array
     */
    public function getOrderData(): array;

    /**
     * Undocumented function
     * @param array $dataList
     * @return array
     */
    public function sortData(array $dataList): array;
}
