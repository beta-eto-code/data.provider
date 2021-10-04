<?php

namespace Data\Provider;

use Data\Provider\Interfaces\CompareRuleInterface;
use DateTime;
use DateTimeImmutable;
use Exception;

class CompareRule implements CompareRuleInterface
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
     * @var CompareRuleInterface[]
     */
    private $or;
    /**
     * @var CompareRuleInterface[]
     */
    private $and;

    public function __construct(string $name, string $operation, $value, ?string $alias = null)
    {
        $this->name = $name;
        $this->alias = $alias;
        $this->operation = $operation;
        $this->value = $value;
        $this->or = [];
        $this->and = [];
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * @return mixed
     */
    public function getCompareValue()
    {
        return $this->value;
    }

    /**
     * @param $modelValue
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

    /**
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function assertWithData(array $data): bool
    {
        $result = $this->assertWithValue($data[$this->name] ?? null);
        if (!$this->isComplex()) {
            return $result;
        }

        if ($result) {
            foreach($this->and as $compareRule) {
                if (!$compareRule->assertWithData($data)) {
                    $result = false;
                    break;
                }
            }
        }

        if (!$result) {
            foreach($this->or as $compareRule) {
                if ($compareRule->assertWithData($data)) {
                    $result = true;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param string $name
     * @param string $operation
     * @param $value
     * @param string|null $alias
     * @return CompareRuleInterface
     */
    public function or(string $name, string $operation, $value, ?string $alias = null): CompareRuleInterface
    {
        $compareRule = new CompareRule($name, $operation, $value, $alias);
        $this->or[] = $compareRule;

        return $compareRule;
    }

    /**
     * @param string $name
     * @param string $operation
     * @param $value
     * @param string|null $alias
     * @return CompareRuleInterface
     */
    public function and(string $name, string $operation, $value, ?string $alias = null): CompareRuleInterface
    {
        $compareRule = new CompareRule($name, $operation, $value);
        $this->and[] = $compareRule;

        return $compareRule;
    }

    /**
     * @return boolean
     */
    public function isComplex(): bool
    {
        return !empty($this->or) || !empty($this->and);
    }

    /**
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @return array
     */
    public function getOrList(): array
    {
        return $this->or ?? [];
    }

    /**
     * @return array
     */
    public function getAndList(): array
    {
        return $this->and ?? [];
    }
}