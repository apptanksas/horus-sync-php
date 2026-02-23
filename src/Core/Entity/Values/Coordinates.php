<?php

namespace AppTank\Horus\Core\Entity\Values;

use AppTank\Horus\Core\Exception\ClientException;

readonly class Coordinates
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
     * @return Coordinates The created Coordinate instance.
     * @throws ClientException If the input format is invalid.
     */
    public static function createFromRaw(?string $raw): ?Coordinates
    {
        if (is_null($raw)) {
            return null;
        }

        $value = trim($raw);

        // sqlite format: "latitude,longitude"
        if (preg_match('/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/', $value)) {
            [$lat, $lng] = array_map('floatval', explode(',', $value));
            return new Coordinates($lat, $lng);
        }

        // Check (Pgsql) format: (longitude,latitude)
        if (is_string($value) && preg_match('/^\((-?\d+\.?\d*),(-?\d+\.?\d*)\)$/', $value, $matches)) {
            return new Coordinates((float)$matches[2], (float)$matches[1]);
        }

        // Check (Pgsql) format: EWKB hexadecimal string
        if (is_string($value) && ctype_xdigit($value) && strlen($value) >= 50) {
            $binary = hex2bin($value);
            // Offset 21 bytes: 1 (byte order) + 4 (type with SRID flag) + 4 (SRID) + 8 (X/longitude) = posición de latitude
            // Los doubles están en little-endian después del SRID
            $longitude = unpack('d', substr($binary, 9, 8))[1];
            $latitude = unpack('d', substr($binary, 17, 8))[1];
            return new Coordinates($latitude, $longitude);
        }

        // MySQL geometry devuelve WKB binario con 4 bytes de SRID al inicio
        // Formato interno WKB: [4 bytes SRID] [1 byte order] [4 bytes type] [8 bytes X] [8 bytes Y]
        // MySQL 8.0+ con SRID 4326 inserta como (lat, lon) pero almacena internamente como (lon, lat) en WKB
        if (is_string($value) && !ctype_xdigit($value) && strlen($value) >= 25) {
            $data = unpack('x4/corder/Vtype/dx/dy', $value);
            if ($data !== false) {
                // WKB almacena: x=longitude, y=latitude
                return new Coordinates($data['y'], $data['x']);
            }
        }

        // Check (MySQL) format: POINT(longitude latitude)
        if (is_string($value) && !str_starts_with($value, 'POINT')) {
            $data = unpack('x4/corder/Vtype/dlongitude/dlatitude', $value);
            if ($data === false) {
                throw new \RuntimeException("Failed to parse coordinates from value: " . $value);
            }
            return new Coordinates($data['latitude'], $data['longitude']);
        }

        if (preg_match('/^POINT\(\s*(-?\d+(\.\d+)?)\s+(-?\d+(\.\d+)?)\s*\)$/i', $value, $matches)) {
            $lng = (float)$matches[1];
            $lat = (float)$matches[3];
            return new Coordinates($lat, $lng);
        }

        throw new ClientException("Invalid coordinate format. Expected 'latitude,longitude' or 'POINT(longitude latitude)'. Received: $raw");
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
