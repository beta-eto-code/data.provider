<?php

namespace Data\Provider\Interfaces;

use Iterator;
use IteratorAggregate;

interface OperationResultInterface extends IteratorAggregate
{
    /**
     * @param bool $recursiveMode
     * @return bool
     */
    public function hasError(bool $recursiveMode = false): bool;

    /**
     * @return string
     */
    public function getErrorMessage(): string;

    /**
     * @return mixed
     */
    public function getData();

    /**
     * @param OperationResultInterface $operationResult
     * @return mixed
     */
    public function addNext(OperationResultInterface $operationResult);

    /**
     * @return Iterator
     */
    public function getErrorIterator(): Iterator;

    /**
     * @return int
     */
    public function getResultCount(): int;

    /**
     * @return int
     */
    public function getErrorResultCount(): int;
}
