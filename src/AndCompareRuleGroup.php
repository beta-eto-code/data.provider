<?php

namespace Data\Provider;

use Data\Provider\Interfaces\AssertableDataInterface;

class AndCompareRuleGroup extends BaseCompareRuleGroup
{
    /**
     * @param string $name
     * @param string $operation
     * @param mixed $value
     * @param string|null $alias
     * @return AndCompareRuleGroup
     */
    public static function create(string $name, string $operation, $value, ?string $alias = null): AndCompareRuleGroup
    {
        return new AndCompareRuleGroup(static::createCompareRule($name, $operation, $value, $alias));
    }

    public function assertWithData(array $data): bool
    {
        foreach ($this->getList() as $rule) {
            if ($rule instanceof AssertableDataInterface && !$rule->assertWithData($data)) {
                return false;
            }
        }

        return true;
    }
}
