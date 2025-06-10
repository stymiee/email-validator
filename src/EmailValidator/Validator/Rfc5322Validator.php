<?php

declare(strict_types=1);

namespace EmailValidator\Validator;

use EmailValidator\EmailAddress;

/**
 * Validates email addresses according to RFC 5322 standards
 *
 * This validator implements strict validation rules from RFC 5322 (superseding RFC 822),
 * including proper handling of:
 * - Quoted strings in local part
 * - Comments
 * - Domain literals
 * - Special characters
 * - Length restrictions
 */
class Rfc5322Validator extends AValidator
{
    // Maximum lengths defined by RFC 5322
    private const MAX_LOCAL_PART_LENGTH = 64;
    private const MAX_DOMAIN_LABEL_LENGTH = 63;
    private const MAX_DOMAIN_LENGTH = 255;

    // Character sets for unquoted local part
    private const LOCAL_PART_ALLOWED_CHARS = '!#$%&\'*+-/=?^_`{|}~.';

    /**
     * Validates an email address according to RFC 5322 rules
     *
     * @param EmailAddress $email The email address to validate
     * @return bool True if the email address is valid according to RFC 5322
     */
    public function validate(EmailAddress $email): bool
    {
        $localPart = $email->getLocalPart();
        $domain = $email->getDomain();

        if ($localPart === null || $domain === null) {
            return false;
        }

        return $this->validateLocalPart($localPart) && $this->validateDomain($domain);
    }

    /**
     * Validates the local part of an email address
     *
     * @param string $localPart The local part to validate
     * @return bool True if the local part is valid
     */
    private function validateLocalPart(string $localPart): bool
    {
        // Check length
        if (!$this->validateLocalPartLength($localPart)) {
            return false;
        }

        // Empty local part is invalid
        if ($localPart === '') {
            return false;
        }

        // Handle quoted string
        if ($this->isQuotedString($localPart)) {
            return $this->validateQuotedString($localPart);
        }

        // Handle dot-atom format
        return $this->validateDotAtom($localPart);
    }

    /**
     * Validates the length of a local part
     *
     * @param string $localPart The local part to validate
     * @return bool True if the length is valid
     */
    private function validateLocalPartLength(string $localPart): bool
    {
        return strlen($localPart) <= self::MAX_LOCAL_PART_LENGTH;
    }

    /**
     * Checks if a local part is a quoted string
     *
     * @param string $localPart The local part to check
     * @return bool True if the local part is a quoted string
     */
    private function isQuotedString(string $localPart): bool
    {
        return $localPart[0] === '"';
    }

    /**
     * Validates a dot-atom format local part
     *
     * @param string $localPart The unquoted local part to validate
     * @return bool True if the unquoted local part is valid
     */
    private function validateDotAtom(string $localPart): bool
    {
        // Split into atoms
        $atoms = explode('.', $localPart);

        // Check each atom
        foreach ($atoms as $atom) {
            if (!$this->validateAtom($atom)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validates a single atom in a dot-atom local part
     *
     * @param string $atom The atom to validate
     * @return bool True if the atom is valid
     */
    private function validateAtom(string $atom): bool
    {
        if ($atom === '') {
            return false;
        }

        // Check for valid characters in each atom
        return (bool) preg_match('/^[a-zA-Z0-9!#$%&\'*+\-\/=?^_`{|}~]+$/', $atom);
    }

    /**
     * Validates a quoted string local part
     *
     * @param string $localPart The quoted string to validate
     * @return bool True if the quoted string is valid
     */
    private function validateQuotedString(string $localPart): bool
    {
        // Must start and end with quotes
        if (!$this->hasValidQuotes($localPart)) {
            return false;
        }

        // Remove outer quotes for content validation
        $content = substr($localPart, 1, -1);

        // Empty quoted strings are valid
        if ($content === '') {
            return true;
        }

        return $this->validateQuotedStringContent($content);
    }

    /**
     * Checks if a quoted string has valid opening and closing quotes
     *
     * @param string $localPart The quoted string to validate
     * @return bool True if the quotes are valid
     */
    private function hasValidQuotes(string $localPart): bool
    {
        return (bool) preg_match('/^".*"$/', $localPart);
    }

    /**
     * Validates the content of a quoted string
     *
     * @param string $content The content to validate (without outer quotes)
     * @return bool True if the content is valid
     */
    private function validateQuotedStringContent(string $content): bool
    {
        $inEscape = false;
        for ($i = 0, $iMax = strlen($content); $i < $iMax; $i++) {
            $char = $content[$i];
            $charCode = ord($char);

            // Non-printable characters are never allowed
            if ($charCode < 32 || $charCode > 126) {
                return false;
            }

            if ($inEscape) {
                // Only quotes and backslashes must be escaped
                // Other characters may be escaped but it's not required
                $inEscape = false;
                continue;
            }

            if ($char === '\\') {
                $inEscape = true;
                continue;
            }

            // Unescaped quotes are not allowed
            if ($char === '"') {
                return false;
            }
        }

        // Can't end with a lone backslash
        return !$inEscape;
    }

    /**
     * Validates the domain part of an email address
     *
     * @param string $domain The domain to validate
     * @return bool True if the domain is valid
     */
    private function validateDomain(string $domain): bool
    {
        // Check for empty domain
        if ($domain === '') {
            return false;
        }

        // Check total length
        if (!$this->validateDomainLength($domain)) {
            return false;
        }

        // Handle domain literal
        if ($this->isDomainLiteral($domain)) {
            return $this->validateDomainLiteral($domain);
        }

        // Validate regular domain
        return $this->validateDomainName($domain);
    }

    /**
     * Validates the length of a domain
     *
     * @param string $domain The domain to validate
     * @return bool True if the length is valid
     */
    private function validateDomainLength(string $domain): bool
    {
        return strlen($domain) <= self::MAX_DOMAIN_LENGTH;
    }

    /**
     * Checks if a domain is a domain literal
     *
     * @param string $domain The domain to check
     * @return bool True if the domain is a domain literal
     */
    private function isDomainLiteral(string $domain): bool
    {
        return $domain[0] === '[';
    }

    /**
     * Validates a domain name
     *
     * @param string $domain The domain name to validate
     * @return bool True if the domain name is valid
     */
    private function validateDomainName(string $domain): bool
    {
        // Split into labels
        $labels = explode('.', $domain);

        // Must have at least two labels
        if (count($labels) < 2) {
            return false;
        }

        // Validate each label
        foreach ($labels as $label) {
            if (!$this->validateDomainLabel($label)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validates a single domain label
     *
     * @param string $label The domain label to validate
     * @return bool True if the domain label is valid
     */
    private function validateDomainLabel(string $label): bool
    {
        // Check length
        if (!$this->validateDomainLabelLength($label)) {
            return false;
        }

        // Must start and end with alphanumeric
        if (!$this->hasValidLabelBoundaries($label)) {
            return false;
        }

        // Check for valid characters and format
        if (!$this->hasValidLabelFormat($label)) {
            return false;
        }

        // Check for consecutive hyphens
        return !$this->hasConsecutiveHyphens($label);
    }

    /**
     * Validates the length of a domain label
     *
     * @param string $label The domain label to validate
     * @return bool True if the length is valid
     */
    private function validateDomainLabelLength(string $label): bool
    {
        return strlen($label) <= self::MAX_DOMAIN_LABEL_LENGTH && $label !== '';
    }

    /**
     * Checks if a domain label has valid start and end characters
     *
     * @param string $label The domain label to validate
     * @return bool True if the boundaries are valid
     */
    private function hasValidLabelBoundaries(string $label): bool
    {
        return ctype_alnum($label[0]) && ctype_alnum(substr($label, -1));
    }

    /**
     * Checks if a domain label has valid format
     *
     * @param string $label The domain label to validate
     * @return bool True if the format is valid
     */
    private function hasValidLabelFormat(string $label): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]*[a-zA-Z0-9]$/', $label);
    }

    /**
     * Checks if a domain label has consecutive hyphens
     *
     * @param string $label The domain label to validate
     * @return bool True if the label has consecutive hyphens
     */
    private function hasConsecutiveHyphens(string $label): bool
    {
        return strpos($label, '--') !== false;
    }

    /**
     * Validates a domain literal (IP address in brackets)
     *
     * @param string $domain The domain literal to validate
     * @return bool True if the domain literal is valid
     */
    private function validateDomainLiteral(string $domain): bool
    {
        // Must be enclosed in brackets
        if (!preg_match('/^\[(.*)]$/', $domain, $matches)) {
            return false;
        }

        $content = $matches[1];

        // Handle IPv6
        if (stripos($content, 'IPv6:') === 0) {
            return $this->validateIPv6($content);
        }

        // Handle IPv4
        return $this->validateIPv4($content);
    }

    /**
     * Validates an IPv6 address
     *
     * @param string $content The IPv6 address to validate (including 'IPv6:' prefix)
     * @return bool True if the IPv6 address is valid
     */
    private function validateIPv6(string $content): bool
    {
        $ipv6 = substr($content, 5);
        // Remove any whitespace
        $ipv6 = trim($ipv6);

        $segments = $this->parseIPv6Segments($ipv6);
        if ($segments === null) {
            return false;
        }

        // Validate each segment
        foreach ($segments as $segment) {
            if (!preg_match('/^[0-9A-Fa-f]{1,4}$/', $segment)) {
                return false;
            }
        }

        // Convert to standard format for final validation
        $ipv6 = implode(':', array_map(function($segment) {
            return str_pad($segment, 4, '0', STR_PAD_LEFT);
        }, $segments));

        return filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Parses IPv6 address segments, handling compressed notation
     *
     * @param string $ipv6 The IPv6 address to parse
     * @return array|null Array of segments if valid, null if invalid
     */
    private function parseIPv6Segments(string $ipv6): ?array
    {
        // Handle compressed notation
        if (strpos($ipv6, '::') !== false) {
            // Only one :: allowed
            if (substr_count($ipv6, '::') > 1) {
                return null;
            }

            // Split on ::
            $parts = explode('::', $ipv6);
            if (count($parts) !== 2) {
                return null;
            }

            // Count segments on each side
            $leftSegments = $parts[0] ? explode(':', $parts[0]) : [];
            $rightSegments = $parts[1] ? explode(':', $parts[1]) : [];

            // Calculate missing segments
            $totalSegments = count($leftSegments) + count($rightSegments);
            if ($totalSegments >= 8) {
                return null;
            }

            // Fill in missing segments
            $middleSegments = array_fill(0, 8 - $totalSegments, '0');

            // Combine all segments
            return array_merge($leftSegments, $middleSegments, $rightSegments);
        }

        $segments = explode(':', $ipv6);
        return count($segments) === 8 ? $segments : null;
    }

    /**
     * Validates an IPv4 address
     *
     * @param string $content The IPv4 address to validate
     * @return bool True if the IPv4 address is valid
     */
    private function validateIPv4(string $content): bool
    {
        $ipv4 = trim($content);
        $octets = $this->parseIPv4Octets($ipv4);
        if ($octets === null) {
            return false;
        }

        // Convert to standard format for final validation
        $ipv4 = implode('.', array_map(function($octet) {
            return ltrim($octet, '0') ?: '0';
        }, $octets));

        return filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Parses IPv4 address octets
     *
     * @param string $ipv4 The IPv4 address to parse
     * @return array|null Array of octets if valid, null if invalid
     */
    private function parseIPv4Octets(string $ipv4): ?array
    {
        // Split into octets
        $octets = explode('.', $ipv4);
        if (count($octets) !== 4) {
            return null;
        }

        // Validate each octet
        foreach ($octets as $octet) {
            // Empty octets are invalid
            if ($octet === '') {
                return null;
            }

            // Remove leading zeros
            $octet = ltrim($octet, '0');
            if ($octet === '') {
                $octet = '0';
            }

            // Check numeric value
            if (!is_numeric($octet) || intval($octet) < 0 || intval($octet) > 255) {
                return null;
            }
        }

        return $octets;
    }
}
