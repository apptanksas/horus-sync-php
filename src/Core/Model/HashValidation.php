<?php

namespace AppTank\Horus\Core\Model;

/**
 * @internal Class HashValidation
 *
 * Represents the validation of a hash comparison between expected and obtained values.
 * This class checks if the obtained hash matches the expected hash.
 *
 * @package AppTank\Horus\Core\Model
 *
 * @author John Ospina
 * Year: 2024
 */
readonly class HashValidation
{
    /**
     * Indicates whether the obtained hash matches the expected hash.
     *
     * @var bool
     */
    public bool $matched;

    /**
     * Constructor for the HashValidation class.
     *
     * @param string $expected The expected hash value.
     * @param string $obtained The obtained hash value to compare against the expected hash.
     */
    function __construct(
        public string $expected,
        public string $obtained
    )
    {
        $this->matched = $this->expected === $this->obtained;
    }
}
