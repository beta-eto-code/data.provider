<?php

namespace Data\Provider\Providers;

use Data\Provider\Interfaces\DataProviderInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;
use Data\Provider\Interfaces\SqlRelationProviderInterface;

abstract class BaseDataProvider implements DataProviderInterface
{
    /**
     * @param QueryCriteriaInterface $query
     * @return array
     */
    abstract protected function getDataInternal(QueryCriteriaInterface $query): array;

    /**
     * @param QueryCriteriaInterface $query
     * @return array
     */
    final public function getData(QueryCriteriaInterface $query): array
    {
        $data = $this->getDataInternal($query);
        foreach ($query->getJoinList() as $joinRule) {
            $joinDataProvider = $joinRule->getDataProvider();
            if ($this instanceof SqlRelationProviderInterface &&
                $joinDataProvider instanceof SqlRelationProviderInterface
            ) {
                continue;
            }

            $joinRule->loadTo($data);
            $joinRule->filterData($data);
        }

        return $data;
    }
}