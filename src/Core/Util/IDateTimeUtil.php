<?php

namespace AppTank\Horus\Core\Util;

/**
 * @internal Interface IDateTimeUtil
 *
 * Defines a contract for handling date and time operations.
 * The implementing class should provide methods to parse date and time values and to get the current date and time.
 *
 * @package AppTank\Horus\Core\Util
 */
interface IDateTimeUtil
{
    const FORMAT_DATE = 'Y-m-d H:i:s';

    /**
     * Parses a date and time value and returns a \DateTime object.
     *
     * This method accepts a date and time value either as a string or an integer (timestamp) and converts it to a \DateTime object.
     *
     * @param string|int $datetime The date and time value to parse. It can be a string representation of a date and time or an integer timestamp.
     * @return \DateTime The parsed \DateTime object.
     *
     * @throws \InvalidArgumentException If the provided value cannot be parsed.
     */
    public function parseDatetime(string|int $datetime): \DateTime;

    /**
     * Returns the current date and time as an immutable \DateTime object.
     *
     * This method provides the current date and time using \DateTimeImmutable, which represents an immutable date and time.
     *
     * @return \DateTimeImmutable The current date and time.
     */
    public function getCurrent(): \DateTimeImmutable;

    /**
     * Returns the date and time in a specific format.
     *
     * This method accepts a timestamp and returns the date and time in a specific format.
     *
     * @param int $timestamp The timestamp to format.
     * @return string The formatted date and time.
     */
    public function getFormatDate(int $timestamp): string;
}
