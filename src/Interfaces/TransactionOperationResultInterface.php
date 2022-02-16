<?php

namespace Data\Provider\Interfaces;

interface TransactionOperationResultInterface extends PkOperationResultInterface
{
    /**
     * @return bool
     */
    public function isFinished(): bool;

    /**
     * @param OperationResultInterface $operationResult
     * @return void
     */
    public function setResult(OperationResultInterface $operationResult);
}
