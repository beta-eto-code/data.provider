<?php

namespace Data\Provider;

use Data\Provider\Interfaces\MigrateResultInterface;
use Data\Provider\Interfaces\StatisticMigrateResultInterface;

class StatisticMigrateResult implements StatisticMigrateResultInterface
{
    private int $successCount;
    private int $errorCount;
    private ?MigrateResultInterface $migrateResult;

    public static function initFromMigrateResult(MigrateResultInterface $migrateResult): StatisticMigrateResultInterface
    {
        [$successCount, $errorCount] = static::calcResult($migrateResult);
        return new StatisticMigrateResult($successCount, $errorCount, $migrateResult);
    }

    public static function initFromCounts(int $successCount, int $errorCount): StatisticMigrateResultInterface
    {
        return new StatisticMigrateResult($successCount, $errorCount, null);
    }

    private static function calcResult(MigrateResultInterface $result): array
    {
        $resultList = $result->getResultList();
        $count = 0;
        $errorCount = 0;
        foreach ($resultList as $r) {
            $count += $r->getResultCount();
            $errorCount += $r->getErrorResultCount();
        }

        $successCount = $count - $errorCount;

        return [$successCount, $errorCount];
    }

    private function __construct(int $successCount, int $errorCount, ?MigrateResultInterface $migrateResult)
    {
        $this->successCount = $successCount;
        $this->errorCount = $errorCount;
        $this->migrateResult = $migrateResult;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function getMigrateResult(): ?MigrateResultInterface
    {
        return $this->migrateResult;
    }
}
