<?php

namespace Data\Provider\Interfaces;

interface StatisticMigrateResultInterface
{
    public function getSuccessCount(): int;
    public function getErrorCount(): int;
    public function getMigrateResult(): ?MigrateResultInterface;
}
