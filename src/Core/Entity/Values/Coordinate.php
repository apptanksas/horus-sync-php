<?php

namespace AppTank\Horus\Core\Entity\Values;

use AppTank\Horus\Core\Exception\ClientException;

readonly class Coordinate
{
    /**
     * Constructor for the Coordinate class.
     *
     * @param float $latitude The latitude of the coordinate.
     * @param float $longitude The longitude of the coordinate.
     */
    public function __construct(
        public float $latitude,
        public float $longitude
    )
    {
    }

    public function __toString(): string
    {
        return $this->latitude . "," . $this->longitude;
    }

    /**
     * Creates a Coordinate instance from a raw string in the format "latitude,longitude".
     *
     * @param string $raw The raw coordinate string.
     * @return Coordinate The created Coordinate instance.
     * @throws ClientException If the input format is invalid.
     */
    public static function createFromRaw(string $raw): Coordinate
    {
        if (preg_match("/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/", $raw) !== 1) {
            throw new ClientException("Invalid coordinate format. Expected 'latitude,longitude'.");
        }

        $parts = explode(",", $raw);

        if (count($parts) !== 2) {
            throw new ClientException("Invalid coordinate format. Expected 'latitude,longitude'.");
        }

        return new Coordinate(floatval($parts[0]), floatval($parts[1]));
    }

    /**
     * Generates a random coordinate in the format "latitude,longitude".
     *
     * @return string The generated raw coordinate string.
     */
    public static function generateRaw(): string
    {
        return (rand(-90, 90) / rand(1, 100)) . "," . (rand(-180, 180) / rand(1, 100));
    }
}