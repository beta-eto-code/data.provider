<?php

namespace Data\Provider\Tests;

use Data\Provider\CompareRule;
use Data\Provider\Interfaces\CompareRuleInterface;
use Exception;
use PHPUnit\Framework\TestCase;

class CompareRuleTest extends TestCase
{
    /**
     * @var CompareRule
     */
    private $defaultRule;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->defaultRule = new CompareRule('id', CompareRuleInterface::EQUAL, 1);
    }

    public function testGetOperation()
    {
        $this->assertEquals(CompareRuleInterface::EQUAL, $this->defaultRule->getOperation());
    }

    public function testGetCompareValue()
    {
        $this->assertEquals(1, $this->defaultRule->getCompareValue());
    }

    public function testGetKey()
    {
        $this->assertEquals('id', $this->defaultRule->getKey());
    }

    public function testOr()
    {
        $rule = clone $this->defaultRule;
        $orCompareRule = $rule->or('id', CompareRuleInterface::EQUAL, 2);
        $actualRule = current($rule->getOrList());
        $this->assertEquals($orCompareRule, $actualRule);
    }

    public function testOrCompareRule()
    {
        $rule = clone $this->defaultRule;
        $orCompareRule = new CompareRule('id', CompareRuleInterface::EQUAL, 2);
        $rule->orCompareRule($orCompareRule);
        $actualRule = current($rule->getOrList());
        $this->assertEquals($orCompareRule, $actualRule);
    }

    public function testIsComplex()
    {
        $rule = clone $this->defaultRule;
        $this->assertFalse($rule->isComplex());

        $rule->or('id', CompareRuleInterface::EQUAL, 2);
        $this->assertTrue($rule->isComplex());
    }

    public function testAnd()
    {
        $rule = clone $this->defaultRule;
        $orCompareRule = $rule->and('active', CompareRuleInterface::EQUAL, true);
        $actualRule = current($rule->getAndList());
        $this->assertEquals($orCompareRule, $actualRule);
    }

    public function testAndCompareRule()
    {
        $rule = clone $this->defaultRule;
        $orCompareRule = new CompareRule('active', CompareRuleInterface::EQUAL, true);
        $rule->andCompareRule($orCompareRule);
        $actualRule = current($rule->getAndList());
        $this->assertEquals($orCompareRule, $actualRule);
    }

    public function testGetAndList()
    {
        $rule = clone $this->defaultRule;
        $this->assertEmpty($rule->getAndList());

        $rule->and('active', CompareRuleInterface::EQUAL, true);
        $this->assertCount(1, $rule->getAndList());

        $rule->and('active', CompareRuleInterface::NOT, false);
        $this->assertCount(2, $rule->getAndList());
    }

    public function testGetOrList()
    {
        $rule = clone $this->defaultRule;
        $this->assertEmpty($rule->getOrList());

        $rule->or('id', CompareRuleInterface::EQUAL, 2);
        $this->assertCount(1, $rule->getOrList());

        $rule->or('id', CompareRuleInterface::EQUAL, 3);
        $this->assertCount(2, $rule->getOrList());
    }

    /**
     * @throws Exception
     */
    public function testAssertWithData()
    {
        $firstDataItem = [
            'id' => 1,
            'title' => 'first item',
        ];
        $secondDataItem = [
            'id' => 2,
            'title' => 'second item',
        ];
        $thirdDataItem = [
            'id' => 3,
            'title' => 'third item',
        ];

        $rule = clone $this->defaultRule;
        $this->assertTrue($rule->assertWithData($firstDataItem));
        $this->assertFalse($rule->assertWithData($secondDataItem));
        $this->assertFalse($rule->assertWithData($thirdDataItem));

        $rule->or('id', CompareRuleInterface::EQUAL, 2);
        $this->assertTrue($rule->assertWithData($firstDataItem));
        $this->assertTrue($rule->assertWithData($secondDataItem));
        $this->assertFalse($rule->assertWithData($thirdDataItem));
    }

    public function testGetAlias()
    {

    }
}
