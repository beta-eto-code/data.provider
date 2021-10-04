<?php

namespace Data\Provider\Interfaces;

interface SqlRelationProviderInterface
{
    /**
     * @return string
     */
    public function getTableName(): string;
}