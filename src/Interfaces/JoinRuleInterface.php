<?php

namespace Data\Provider\Interfaces;

interface JoinRuleInterface
{
    const INNER_TYPE = 'INNER';
    const LEFT_TYPE = 'LEFT';
    const RIGHT_TYPE = 'RIGHT';

    /**
     * @param string $type
     * @return void
     */
    public function setType(string $type);

    /**
     * @param string $alias
     * @return void
     */
    public function setAlias(string $alias);

    /**
     * @return string|null
     */
    public function getAlias(): ?string;

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @return string
     */
    public function getForeignKey(): string;

    /**
     * @param CompareRuleInterface $compareRule
     * @return void
     */
    public function setAdditionFilterByJoinData(CompareRuleInterface $compareRule);

    /**
     * @return string
     */
    public function getDestKey(): string;

    /**
     * @param $data
     * @return mixed
     */
    public function loadTo(&$data);

    /**
     * @param $data
     * @return mixed
     */
    public function filterData(&$data);

    /**
     * @return DataProviderInterface
     */
    public function getDataProvider(): DataProviderInterface;

    /**
     * @return QueryCriteriaInterface|null
     */
    public function getQueryCriteria(): ?QueryCriteriaInterface;
}