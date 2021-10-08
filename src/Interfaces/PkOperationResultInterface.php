<?php

namespace Data\Provider\Interfaces;

interface PkOperationResultInterface extends OperationResultInterface
{
    /**
     * @return mixed
     */
    public function getPk();
}