<?php

namespace Data\Provider;

use Data\Provider\Interfaces\OperationResultInterface;
use Data\Provider\Interfaces\PkOperationResultInterface;
use EmptyIterator;
use Iterator;
use Traversable;

class OperationResult implements PkOperationResultInterface
{
    /**
     * @var string|null
     */
    private $errorMessage;
    /**
     * @var mixed|null
     */
    private $data;
    /**
     * @var mixed|null
     */
    private $pk;
    /**
     * @var OperationResultInterface
     */
    private $next;

    public function __construct(string $errorMessage = null, $data = null, $pk = null)
    {
        $this->errorMessage = $errorMessage;
        $this->data = $data;
        $this->pk = $pk;
    }

    /**
     * @param OperationResultInterface $operationResult
     * @return void
     */
    public function addNext(OperationResultInterface $operationResult)
    {
        if ($this->next instanceof OperationResultInterface) {
            $this->next->addNext($operationResult);
        }
        $this->next = $operationResult;
    }

    /**
     * @return mixed|null
     */
    public function getPk()
    {
        return $this->pk;
    }

    /**
     * @param bool $recursiveMode
     * @return bool
     */
    public function hasError(bool $recursiveMode = false): bool
    {
        if (!empty($this->errorMessage)) {
            return true;
        }

        if ($recursiveMode) {
            return $this->next instanceof OperationResultInterface && $this->next->hasError($recursiveMode);
        }

        return false;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return (string)$this->errorMessage;
    }

    /**
     * @return mixed|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return OperationResultInterface[]|Traversable
     */
    public function getIterator()
    {
        yield $this;

        if ($this->next instanceof OperationResultInterface) {
            yield $this->next;

            foreach ($this->getIterator() as $operationResult) {
                yield $operationResult;
            }
        }
    }

    /**
     * @return Iterator
     */
    public function getErrorIterator(): Iterator
    {
        foreach ($this->getIterator() as $operationResult) {
            if ($operationResult->hasError()) {
                yield $operationResult;
            }
        }

        return new EmptyIterator();
    }
}