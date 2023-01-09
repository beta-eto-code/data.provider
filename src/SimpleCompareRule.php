<?php

namespace Data\Provider;

use Data\Provider\Interfaces\AssertableDataInterface;
use Data\Provider\Interfaces\CompareRuleInterface;
use DateTime;
use DateTimeImmutable;
use Exception;

class SimpleCompareRule implements CompareRuleInterface, AssertableDataInterface
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string|null
     */
    private $alias;
    /**
     * @var string
     */
    private $operation;
    /**
     * @var mixed
     */
    private $value;

    /**
     * @param string $name
     * @param string $operation
     * @param mixed $value
     * @param string|null $alias
     */
    public function __construct(string $name, string $operation, $value, ?string $alias = null)
    {
        $this->name = $name;
        $this->alias = $alias;
        $this->operation = $operation;
        $this->value = $value;
    }

    public function getKey(): string
    {
        return $this->name;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function getCompareValue()
    {
        return $this->value;
    }

    /**
     * @throws Exception
     */
    public function assertWithData(array $data): bool
    {
        return $this->assertWithValue($data[$this->name] ?? null);
    }

    /**
     * @param mixed $modelValue
     * @return bool
     * @throws Exception
     */
    private function assertWithValue($modelValue): bool
    {
        $localValue = $this->value;
        if ($localValue instanceof DateTime || $localValue instanceof DateTimeImmutable) {
            $localValue = $localValue->getTimestamp();

            $modelDate = DateTime::createFromFormat('Y-m-d H:i:s', $modelValue);
            if (!$modelDate) {
                $modelDate = new DateTime($modelValue) ?? null;
            }
            $modelValue = $modelDate instanceof DateTime ? $modelDate->getTimestamp() : 0;
        }

        switch ($this->operation) {
            case CompareRuleInterface::NOT:
                return $modelValue != $localValue;
            case CompareRuleInterface::LIKE:
                if (!is_string($modelValue)) {
                    return false;
                }

                $value = str_replace('%', '', $localValue);
                return stripos($modelValue, $value) !== false;
            case CompareRuleInterface::NOT_LIKE:
                if (!is_string($modelValue)) {
                    return false;
                }

                $value = str_replace('%', '', $localValue);
                return stripos($modelValue, $value) === false;
            case CompareRuleInterface::LESS:
                if (is_null($modelValue)) {
                    return false;
                }

                return $modelValue < $localValue;
            case CompareRuleInterface::MORE:
                if (is_null($modelValue)) {
                    return false;
                }

                return $modelValue > $localValue;
            case CompareRuleInterface::LESS_OR_EQUAL:
                if (is_null($modelValue)) {
                    return false;
                }

                return $modelValue <= $localValue;
            case CompareRuleInterface::MORE_OR_EQUAL:
                if (is_null($modelValue)) {
                    return false;
                }

                return $modelValue >= $localValue;
            case CompareRuleInterface::IN:
                return in_array($modelValue, (array)$localValue);
            case CompareRuleInterface::NOT_IN:
                return !in_array($modelValue, (array)$localValue);
            case CompareRuleInterface::BETWEEN:
                if (!is_array($modelValue) || count($modelValue) !== 2) {
                    return false;
                }

                $firstValue = array_shift($modelValue);
                $secondValue = array_shift($modelValue);

                return $firstValue < $localValue && $localValue < $secondValue;
            case CompareRuleInterface::NOT_BETWEEN:
                if (!is_array($modelValue) || count($modelValue) !== 2) {
                    return false;
                }

                $firstValue = array_shift($modelValue);
                $secondValue = array_shift($modelValue);

                return !($firstValue < $localValue) && !($localValue < $secondValue);
        }

        return $modelValue == $localValue;
    }

    public function isComplex(): bool
    {
        return false;
    }
}
