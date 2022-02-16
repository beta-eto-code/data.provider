<?php

namespace Data\Provider;

use Data\Provider\Interfaces\OperationResultInterface;
use Data\Provider\Interfaces\PkOperationResultInterface;
use Data\Provider\Interfaces\TransactionOperationResultInterface;
use EmptyIterator;
use Exception;
use Iterator;
use Traversable;

class TransactionOperationResult implements TransactionOperationResultInterface
{
    /**
     * @var OperationResultInterface|null
     */
    private $result;

    /**
     * @return EmptyIterator|Traversable
     *
     * @throws Exception
     */
    public function getIterator()
    {
        if ($this->result instanceof OperationResultInterface) {
            return $this->result->getIterator();
        }

        return new EmptyIterator();
    }

    /**
     * @param bool $recursiveMode
     * @return bool
     */
    public function hasError(bool $recursiveMode = false): bool
    {
        if ($this->result instanceof OperationResultInterface) {
            return $this->result->hasError($recursiveMode);
        }

        return false;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        if ($this->result instanceof OperationResultInterface) {
            return $this->result->getErrorMessage();
        }

        return '';
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        if ($this->result instanceof OperationResultInterface) {
            return $this->result->getData();
        }

        return null;
    }

    /**
     * @param OperationResultInterface $operationResult
     * @return void
     */
    public function addNext(OperationResultInterface $operationResult)
    {
        if ($this->result instanceof OperationResultInterface) {
            $this->result->addNext($operationResult);
            return;
        }

        $this->setResult($operationResult);
    }

    /**
     * @return Iterator
     */
    public function getErrorIterator(): Iterator
    {
        if ($this->result instanceof OperationResultInterface) {
            return $this->result->getErrorIterator();
        }

        return new EmptyIterator();
    }

    /**
     * @return mixed
     */
    public function getPk()
    {
        if ($this->result instanceof PkOperationResultInterface) {
            return $this->result->getPk();
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isFinished(): bool
    {
        return $this->result instanceof OperationResultInterface;
    }

    /**
     * @param OperationResultInterface $operationResult
     * @return void
     */
    public function setResult(OperationResultInterface $operationResult)
    {
        $this->result = $operationResult;
    }

    /**
     * @return int
     */
    public function getResultCount(): int
    {
        return $this->result instanceof OperationResultInterface ? $this->result->getResultCount() : 0;
    }

    /**
     * @return int
     */
    public function getErrorResultCount(): int
    {
        return $this->result instanceof OperationResultInterface ? $this->result->getErrorResultCount() : 0;
    }
}
