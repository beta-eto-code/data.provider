<?php

namespace Data\Provider;

use Data\Provider\Interfaces\PkOperationResultInterface;

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

    public function __construct(string $errorMessage = null, $data = null, $pk = null)
    {
        $this->errorMessage = $errorMessage;
        $this->data = $data;
        $this->pk = $pk;
    }

    /**
     * @return mixed|null
     */
    public function getPk()
    {
        return $this->pk;
    }

    /**
     * @return bool
     */
    public function hasError(): bool
    {
        return !empty($this->errorMessage);
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
}