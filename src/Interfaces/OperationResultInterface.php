<?php

namespace Data\Provider\Interfaces;

interface OperationResultInterface
{
    /**
     * @return bool
     */
    public function hasError(): bool;

    /**
     * @return string
     */
    public function getErrorMessage(): string;

    /**
     * @return mixed
     */
    public function getData();
}