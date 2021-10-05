<?php

namespace Data\Provider;

use Data\Provider\Interfaces\DataCheckerInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;

class DefaultDataChecker implements DataCheckerInterface
{
    /**
     * @var QueryCriteriaInterface
     */
    private $query;

    /**
     * @var int
     */
    private $count;

    public function __construct(QueryCriteriaInterface $query)
    {
        $this->query = $query;
        $this->offset = $query->getOffset();
        $this->count = 0;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function assertDataByCriteria(array $data): bool
    {
        if ($this->failByLimit()) {
            return false;
        }

        foreach ($this->query->getCriteriaList() as $compareRule) {
            if (!$compareRule->assertWithData($data)) {
                return false;
            }
        }

        if ($this->offset > 0) {
            $this->offset--;
            return false;
        }

        $this->count++;

        return true;
    }

    /**
     * @param array $dataList
     * @return array
     */
    public function filterDataList(array $dataList): array
    {
        $result = [];
        foreach ($dataList as $item) {
            if ($this->failByLimit()) {
                break;
            }

            if ($this->assertDataByCriteria($item)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function failByLimit(): bool
    {
        $limit = $this->query->getLimit();
        return $limit > 0 && $this->count >= $limit;
    }

    /**
     * @return int
     */
    public function successCount(): int
    {
        return $this->count;
    }
}
