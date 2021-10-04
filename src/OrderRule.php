<?php

namespace Data\Provider;

use Closure;
use Data\Provider\Interfaces\OrderRuleInterface;

class OrderRule implements OrderRuleInterface
{
    /**
     * @var array
     */
    private $orderList;

    public function __construct()
    {
        $this->orderList = [];
    }

    /**
     * @param string $key
     * @param boolean $isAscending
     * @param string|null $alias
     * @return void
     */
    public function setOrderByKey(string $key, bool $isAscending = true, ?string $alias = null)
    {
        $this->orderList[$key] = [
            'isAscending' => $isAscending,
            'alias' => $alias,
        ];
    }

    /**
     * @return Closure
     */
    private function internalSort(): Closure
    {
        $ruleCount = count($this->orderList);
        return function($a, $b) use ($ruleCount) {
            $aResult = 0;
            $bResult = 0;
            $index = 0;
            foreach($this->orderList as $key => $data) {
                $isAscending = (bool)$data['isAscending'];
                $aValue = $a[$key] ?? 0;
                $bValue = $b[$key] ?? 0;

                if (
                    ($isAscending && $aValue < $bValue) ||
                    (!$isAscending && $aValue > $bValue)
                ){
                    $aResult += 1 << ($ruleCount-$index);
                } else {
                    $bResult += 1 << ($ruleCount-$index);
                }

                $index++;
            }

            return $aResult > $bResult ? -1 : 1;
        };
    }

    /**
     * @param array $dataList
     * @return array
     */
    public function sortData(array $dataList): array
    {
        if ($this->isEmpty()) {
            return $dataList;
        }

        usort($dataList, $this->internalSort());
        return $dataList;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->orderList);
    }

    /**
     * @return array
     */
    public function getOrderData(): array
    {
        return $this->orderList;
    }
}