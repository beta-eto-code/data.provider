<?php

namespace Data\Provider;

use Data\Provider\Interfaces\OperationResultInterface;

class OperationResult implements OperationResultInterface
{
    /**
     * @var string|null
     */
    private $errorMessage;
    /**
     * @var mixed|null
     */
    private $data;

    public function __construct(string $errorMessage = null, $data = null)
    {
        $this->errorMessage = $errorMessage;
        $this->data = $data;
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