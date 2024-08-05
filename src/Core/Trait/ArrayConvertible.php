<?php

namespace AppTank\Horus\Core\Trait;

use AppTank\Horus\Core\Util\StringUtil;

trait ArrayConvertible
{
    function toArrayData(): array
    {
        return $this->getAttributesFromClass(get_called_class());
    }

    private function getAttributesFromClass(string $className
    ): array
    {
        $class = new \ReflectionClass($className);
        $data = [];

        foreach ($class->getProperties() as $property) {

            $attrDisplayName = StringUtil::snakeCase($property->name);
            $data[$attrDisplayName] = $this->{$property->name};
        }


        return $data;
    }
}
