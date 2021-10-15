<?php

namespace Data\Provider\Providers\Traits;

use Data\Provider\Interfaces\OperationResultInterface;
use EmptyIterator;
use Iterator;

trait OperationStorageTrait
{
    /**
     * @var callable[]
     */
    protected $operationList = [];

    protected function addOperation(callable $fn)
    {
        $this->operationList[] = $fn;
    }

    /**
     * @return OperationResultInterface[]|Iterator
     */
    protected function executeOperations(): Iterator
    {
        foreach ($this->operationList as $fnOperation) {
            yield $fnOperation();
        }

        $this->clearOperations();

        return new EmptyIterator();
    }

    protected function clearOperations()
    {
        $this->operationList = [];
    }
}