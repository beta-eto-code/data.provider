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

    protected function addOperation(callable $fn): void
    {
        $this->operationList[] = $fn;
    }

    /**
     * @psalm-return \Generator<int, mixed, mixed, \EmptyIterator>
     */
    protected function executeOperations(): \Generator
    {
        foreach ($this->operationList as $fnOperation) {
            yield $fnOperation();
        }

        $this->clearOperations();

        return new EmptyIterator();
    }

    protected function clearOperations(): void
    {
        $this->operationList = [];
    }
}
