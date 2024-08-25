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

    /**
     * Generate a list of paths for the entity map
     *
     * @param string $separator
     * @return array|string[]
     */
    public function generateArrayPaths(): array
    {
        $output = [];

        if (empty($this->related)) {
            return [$this->name];
        }

        $groupPaths = array_map(fn($map) => $map->generateArrayPaths(), $this->related);

        foreach ($groupPaths as $paths) {
            $output[] = array_merge([$this->name], $paths);
        }

        return $output;
    }
}