<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Model\EntityData;

readonly abstract class BaseGetEntities
{
    /**
     * @param EntityData[] $entities
     * @return array
     */
    protected function parseData(array $entities): array
    {
        $output = [];

        foreach ($entities as $entity) {
            $data = $entity->getData();

            foreach ($data as $key => $value) {

                // Validate if is a related entity
                if (str_starts_with($key, "_") and is_array($value)) {
                    $data[$key] = $this->parseData($value);
                }

                if (str_starts_with($key, "_") and $value instanceof EntityData) {
                    $data[$key] = ["entity" => $value->name, "data" => $value->getData()];
                }

            }
            $output[] = ["entity" => $entity->name, "data" => $data];
        }

        return $output;
    }
}