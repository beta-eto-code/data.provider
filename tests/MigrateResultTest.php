<?php

namespace Data\Provider\Tests;

use Data\Provider\DefaultDataMigrator;
use Data\Provider\Interfaces\CompareRuleInterface;
use Data\Provider\Providers\ArrayDataProvider;
use Data\Provider\QueryCriteria;
use PHPUnit\Framework\TestCase;

class MigrateResultTest extends TestCase
{
    /**
     * @var ArrayDataProvider
     */
    private $sourceProvider;
    /**
     * @var ArrayDataProvider
     */
    private $targetProvider;
    /**
     * @var DefaultDataMigrator
     */
    private $migrator;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->sourceProvider = new ArrayDataProvider([
            [
                'id' => 1,
                'title' => 'title 1',
            ],
            [
                'id' => 2,
                'title' => 'title 2',
            ],
            [
                'id' => 3,
                'title' => 'title 3',
            ],
            [
                'id' => 4,
                'title' => 'title 4',
            ],
        ]);
        $this->targetProvider = new ArrayDataProvider([]);
        $this->migrator = new DefaultDataMigrator($this->sourceProvider, $this->targetProvider);
    }

    public function testGetSourceData()
    {
        $query = new QueryCriteria();
        $query->addCriteria('id', CompareRuleInterface::LESS, 4);
        $resultInsert = $this->migrator->runInsert($query);

        $expected = [
            [
                'id' => 1,
                'title' => 'title 1',
            ],
            [
                'id' => 2,
                'title' => 'title 2',
            ],
            [
                'id' => 3,
                'title' => 'title 3',
            ]
        ];
        $this->assertEquals($expected, $resultInsert->getSourceData());
    }

    public function testGetResultList()
    {
        $query = new QueryCriteria();
        $query->addCriteria('id', CompareRuleInterface::LESS, 4);
        $resultInsert = $this->migrator->runInsert($query);
        $this->assertCount(3, $resultInsert->getResultList());
    }

    public function testGetErrors()
    {
        $query = new QueryCriteria();
        $query->addCriteria('id', CompareRuleInterface::LESS, 4);
        $resultInsert = $this->migrator->runInsert($query);
        $this->assertCount(0, $resultInsert->getErrors());
    }

    public function testHasErrors()
    {
        $query = new QueryCriteria();
        $query->addCriteria('id', CompareRuleInterface::LESS, 4);
        $resultInsert = $this->migrator->runInsert($query);
        $this->assertFalse($resultInsert->hasErrors());
    }

    public function testGetQuery()
    {
        $query = new QueryCriteria();
        $query->addCriteria('id', CompareRuleInterface::LESS, 4);
        $resultInsert = $this->migrator->runInsert($query);
        $this->assertEquals($query, $resultInsert->getQuery());
    }

    public function testGetUnimportedDataList()
    {
        $query = new QueryCriteria();
        $query->addCriteria('id', CompareRuleInterface::LESS, 4);
        $resultInsert = $this->migrator->runInsert($query);
        $this->assertCount(0, $resultInsert->getUnimportedDataList());
    }
}
