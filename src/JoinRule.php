<?php

namespace Data\Provider;

use Data\Provider\Interfaces\CompareRuleInterface;
use Data\Provider\Interfaces\DataProviderInterface;
use Data\Provider\Interfaces\JoinRuleInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;

class JoinRule implements JoinRuleInterface
{

    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $alias;
    /**
     * @var DataProviderInterface
     */
    private $dataProvider;
    /**
     * @var string
     */
    private $foreignKey;
    /**
     * @var string
     */
    private $destKey;
    /**
     * @var QueryCriteriaInterface|null
     */
    private $query;
    /**
     * @var CompareRuleInterface|null
     */
    private $filterByJoinData;

    public function __construct(
        DataProviderInterface $dataProvider,
        string $foreignKey,
        string $destKey,
        ?QueryCriteriaInterface $query = null
    )
    {
        $this->dataProvider = $dataProvider;
        $this->foreignKey = $foreignKey;
        $this->destKey = $destKey;
        $this->query = $query;
    }

    public function setType(string $type)
    {
        if (in_array(
            $type,
            [JoinRuleInterface::INNER_TYPE, JoinRuleInterface::LEFT_TYPE, JoinRuleInterface::RIGHT_TYPE])
        ) {
            $this->type = $type;
        }
    }

    /**
     * @param string $alias
     */
    public function setAlias(string $alias)
    {
        $this->alias = $alias;
    }

    /**
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type ?? JoinRuleInterface::LEFT_TYPE;
    }

    /**
     * @return string
     */
    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    /**
     * @return string
     */
    public function getDestKey(): string
    {
        return $this->destKey;
    }

    /**
     * @param $data
     * @return void
     */
    public function loadTo(&$data)
    {
        if (!($this->query instanceof QueryCriteriaInterface) || empty($this->query->getSelect())) {
            return;
        }

        $keyValues = array_column($data, $this->foreignKey);
        if (empty($keyValues)) {
            return;
        }

        $query = clone $this->query;
        $query->addCriteria($this->destKey, CompareRuleInterface::IN, $keyValues);

        $result = [];
        $destDataList = $this->dataProvider->getData($this->query);
        $select = $this->query->getSelect();

        foreach ($data as $i => $item) {
            $count = 0;
            foreach ($destDataList as $destItem) {
                if ($destItem[$this->destKey] === $item[$this->foreignKey]) {
                    $count++;
                    $dataForMerge = [];
                    foreach ($select as $key) {
                        $dataForMerge[$key] = $destItem[$key] ?? null;
                    }
                    $result[] = array_merge($item, $dataForMerge);
                }
            }

            if ($count === 0 && $this->type === JoinRuleInterface::LEFT_TYPE) {
                $result[] = $item;
            }
        }

        $data = $result;
    }

    /**
     * @return DataProviderInterface
     */
    public function getDataProvider(): DataProviderInterface
    {
        return $this->dataProvider;
    }

    /**
     * @return QueryCriteriaInterface|null
     */
    public function getQueryCriteria(): ?QueryCriteriaInterface
    {
        return $this->query;
    }

    /**
     * @param CompareRuleInterface $compareRule
     * @return void
     */
    public function setAdditionFilterByJoinData(CompareRuleInterface $compareRule)
    {
        $this->filterByJoinData = $compareRule;
    }

    /**
     * @param $data
     * @return void
     */
    public function filterData(&$data)
    {
        if (!($this->filterByJoinData instanceof CompareRuleInterface)) {
            return;
        }

        $result = [];
        foreach($data as $itemData) {
            if ($this->filterByJoinData->assertWithData($itemData)) {
                $result[] = $itemData;
            }
        }

        $data = $result;
    }
}