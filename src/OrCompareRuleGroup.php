<?php

namespace Data\Provider;

use Data\Provider\Interfaces\AssertableDataInterface;

class OrCompareRuleGroup extends BaseCompareRuleGroup
{
    /**
     * @param string $name
     * @param string $operation
     * @param mixed $value
     * @param string|null $alias
     * @return OrCompareRuleGroup
     */
    public static function create(string $name, string $operation, $value, ?string $alias = null): OrCompareRuleGroup
    {
        return new OrCompareRuleGroup(static::createCompareRule($name, $operation, $value, $alias));
    }

    public function assertWithData(array $data): bool
    {
        foreach ($this->getList() as $rule) {
            if ($rule instanceof AssertableDataInterface && $rule->assertWithData($data)) {
                return true;
            }
        }

        return false;
    }
}
