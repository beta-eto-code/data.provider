<?php

namespace Data\Provider\Tests;

use Data\Provider\OrderRule;
use PHPUnit\Framework\TestCase;

class OrderRuleTest extends TestCase
{
    /**
     * @var OrderRule
     */
    private $orderRule;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->orderRule = new OrderRule();
        $this->orderRule->setOrderByKey('id', true);
    }

    public function testSortData()
    {
        $dataForSort = [
            ['id' => 3],
            ['id' => 1],
            ['id' => 4],
            ['id' => 2]
        ];

        $expectedData = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
            ['id' => 4]
        ];

        $sortedData = $this->orderRule->sortData($dataForSort);
        $this->assertEquals($expectedData, $sortedData);
    }

    public function testSetOrderByKey()
    {
        $newSortKey = 'test';
        $this->orderRule->setOrderByKey($newSortKey, false);
        $orderDataList = $this->orderRule->getOrderData();
        $orderDataRule = $orderDataList[$newSortKey] ?? null;

        $this->assertNotEmpty($orderDataRule);
        $this->assertFalse($orderDataRule['isAscending']);
    }

    public function testIsEmpty()
    {
        $orderRule = new OrderRule();
        $this->assertTrue($orderRule->isEmpty());

        $orderRule->setOrderByKey('id', false);
        $this->assertFalse($orderRule->isEmpty());
    }

    public function testGetOrderData()
    {
        $expectedData = [
            'id' => [
                'isAscending' => true,
                'alias' => null
            ]
        ];

        $this->assertEquals($expectedData, $this->orderRule->getOrderData());
    }
}
