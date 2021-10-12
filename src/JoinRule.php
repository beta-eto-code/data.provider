<?php

namespace Data\Provider;

use Bitrix\Crm\ConfigChecker\Iterator;
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
    /**
     * @var array
     */
    private $allItems;

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
     * @return array
     */
    private function getAll(): array
    {
        if (!empty($this->allItems)) {
            return $this->allItems;
        }

        return $this->allItems = $this->dataProvider->getData(new QueryCriteria());
    }

    /**
     * @param $item
     * @param array|null $destItems
     * @param array|null $select
     * @return \Iterator
     */
    public function processJoinToItem($item, array $destItems = null, array $select = null): \Iterator
    {
        $count = 0;
        $select = $select ?? $this->query->getSelect();

        foreach ($destItems ?? $this->getAll() as $destItem) {
            if ($destItem[$this->destKey] === $item[$this->foreignKey]) {
                $count++;
                $dataForMerge = [];
                foreach ($select as $key) {
                    $dataForMerge[$key] = $destItem[$key] ?? null;
                }
                yield array_merge($item, $dataForMerge);
            }
        }

        if ($count === 0 && $this->type === JoinRuleInterface::LEFT_TYPE) {
            yield $item;
        }
    }

    /**
     * @param $data
     * @return void
     */
    public function loadTo(&$data, array $select = null)
    {
        $select = $select ?? $this->query->getSelect();
        if (empty($select)) {
            return;
        }

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

        foreach ($data as $i => $item) {
            foreach ($this->processJoinToItem($item, $destDataList, $select) as $resultItem) {
                $result[] = $resultItem;
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
     * @param array $item
     * @return bool
     */
    public function assertItem(array $item): bool
    {
        return $this->filterByJoinData->assertWithData($itemData);
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
            if ($this->assertItem($itemData)) {
                $result[] = $itemData;
            }
        }

        $data = $result;
    }
}