<?php

namespace AppTank\Horus\Core;

readonly class EntityMap
{
    /**
     * @param string $name
     * @param EntityMap[] $related
     */
    public function __construct(
        public string $name,
        public array  $related = []
    )
    {

    }
}