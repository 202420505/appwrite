<?php

namespace Appwrite\Network\Validator;

use Utopia\Validator\Host;

/**
 * Redirect
 *
 * Validate that URL has an allowed host for redirect
 *
 * @package Utopia\Validator
 */
class Redirect extends Host
{
    /**
     * @param array $whitelist
     */
    public function __construct(array $whitelist)
    {
        parent::__construct($whitelist);
    }

    /**
     * Get Description
     *
     * Returns validator description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'HTTP or HTTPS URL host must be one of: ' . \implode(', ', $this->whitelist);
    }

    /**
     * Is valid
     *
     * Validation will pass if scheme is not http or https or host is in whitelist
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        $url = \parse_url($value);

        if ($url === false || !isset($url['scheme'])) {
            return false;
        }

        $scheme = strtolower($url['scheme']);

        if ($scheme === 'javascript') {
            return false;
        }

        if (!\in_array($scheme, ['http', 'https'])) {
            return true;
        }

        return parent::isValid($value);
    }
}
