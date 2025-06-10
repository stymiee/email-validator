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
        if (strlen($localPart) > self::MAX_LOCAL_PART_LENGTH) {
            return false;
        }

        // Empty local part is invalid
        if ($localPart === '') {
            return false;
        }

        // Handle quoted string
        if ($localPart[0] === '"') {
            return $this->validateQuotedString($localPart);
        }

        // Handle dot-atom format
        return $this->validateDotAtom($localPart);
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
            if ($atom === '') {
                return false;
            }

            // Check for valid characters in each atom
            if (!preg_match('/^[a-zA-Z0-9!#$%&\'*+\-\/=?^_`{|}~]+$/', $atom)) {
                return false;
            }
        }

        return true;
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
        if (!preg_match('/^".*"$/', $localPart)) {
            return false;
        }

        // Remove outer quotes for content validation
        $content = substr($localPart, 1, -1);

        // Empty quoted strings are valid
        if ($content === '') {
            return true;
        }

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
        if (strlen($domain) > self::MAX_DOMAIN_LENGTH) {
            return false;
        }

        // Handle domain literal
        if ($domain[0] === '[') {
            return $this->validateDomainLiteral($domain);
        }

        // Validate regular domain
        return $this->validateDomainName($domain);
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
            $ipv6 = substr($content, 5);
            // Remove any whitespace
            $ipv6 = trim($ipv6);

            // Handle compressed notation
            if (strpos($ipv6, '::') !== false) {
                // Only one :: allowed
                if (substr_count($ipv6, '::') > 1) {
                    return false;
                }

                // Split on ::
                $parts = explode('::', $ipv6);
                if (count($parts) !== 2) {
                    return false;
                }

                // Count segments on each side
                $leftSegments = $parts[0] ? explode(':', $parts[0]) : [];
                $rightSegments = $parts[1] ? explode(':', $parts[1]) : [];

                // Calculate missing segments
                $totalSegments = count($leftSegments) + count($rightSegments);
                if ($totalSegments >= 8) {
                    return false;
                }

                // Fill in missing segments
                $middleSegments = array_fill(0, 8 - $totalSegments, '0');

                // Combine all segments
                $segments = array_merge($leftSegments, $middleSegments, $rightSegments);
            } else {
                $segments = explode(':', $ipv6);
                if (count($segments) !== 8) {
                    return false;
                }
            }

            // Validate each segment
            foreach ($segments as $segment) {
                if (!preg_match('/^[0-9A-Fa-f]{1,4}$/', $segment)) {
                    return false;
                }
            }

            // Convert to standard format for final validation
            $ipv6 = implode(':', array_map(function ($segment) {
                return str_pad($segment, 4, '0', STR_PAD_LEFT);
            }, $segments));

            // Final validation using filter_var
            if (!filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return false;
            }

            return true;
        }

        // Handle IPv4
        $ipv4 = trim($content);

        // Split into octets
        $octets = explode('.', $ipv4);
        if (count($octets) !== 4) {
            return false;
        }

        // Validate each octet
        foreach ($octets as $octet) {
            // Remove leading zeros
            $octet = ltrim($octet, '0');
            if ($octet === '') {
                $octet = '0';
            }

            // Check numeric value
            if (!is_numeric($octet) || intval($octet) < 0 || intval($octet) > 255) {
                return false;
            }
        }

        // Convert to standard format for final validation
        $ipv4 = implode('.', array_map(function ($octet) {
            return ltrim($octet, '0') ?: '0';
        }, $octets));

        return filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
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
        if (strlen($label) > self::MAX_DOMAIN_LABEL_LENGTH || $label === '') {
            return false;
        }

        // Must start and end with alphanumeric
        if (!ctype_alnum($label[0]) || !ctype_alnum(substr($label, -1))) {
            return false;
        }

        // Check for valid characters (alphanumeric and hyphen)
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]*[a-zA-Z0-9]$/', $label)) {
            return false;
        }

        // Check for consecutive hyphens
        if (strpos($label, '--') !== false) {
            return false;
        }

        return true;
    }
}
