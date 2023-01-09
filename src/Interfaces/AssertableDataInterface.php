<?php

namespace Data\Provider\Interfaces;

interface AssertableDataInterface
{
    /**
     * @param array $data
     * @return bool
     */
    public function assertWithData(array $data): bool;
}
