<?php

declare(strict_types=1);

namespace EmailValidator;

class EmailAddress
{
    /**
     * @var string
     */
    private string $email;

    /**
     * @var string|null
     */
    private ?string $localPart = null;

    /**
     * @var string|null
     */
    private ?string $domain = null;

    public function __construct(string $email)
    {
        $this->email = $email;
        $this->parseEmail();
    }

    /**
     * Parses the email address into local part and domain
     * 
     * This method handles:
     * - Multiple @ symbols in quoted strings
     * - Domain literals (IP addresses in square brackets)
     * - Comments in email addresses
     * 
     * @return void
     */
    private function parseEmail(): void
    {
        // First, remove any comments
        $email = preg_replace('/\([^)]*\)/', '', $this->email);
        
        // Handle domain literals (IP addresses in square brackets)
        if (preg_match('/\[([^\]]+)\]$/', $email, $matches)) {
            $this->domain = $matches[1];
            $this->localPart = substr($email, 0, strrpos($email, '@'));
            return;
        }

        // Split on the last @ symbol
        $parts = explode('@', $email);
        if (count($parts) < 2) {
            return;
        }

        $this->domain = end($parts);
        array_pop($parts);
        $this->localPart = implode('@', $parts);
    }

    /**
     * Returns the domain name portion of the email address.
     *
     * @return string|null
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Returns the email address.
     *
     * @return string
     */
    public function getEmailAddress(): string
    {
        return $this->email;
    }

    /**
     * Returns the username of the email address.
     *
     * @since 1.1.0
     * @return string
     */
    private function getUsername(): string
    {
        return $this->localPart ?? '';
    }

    /**
     * Determines if a gmail account is using the "plus trick".
     *
     * @since 1.1.0
     * @return bool
     */
    public function isGmailWithPlusChar(): bool
    {
        $result = false;
        if (in_array($this->getDomain(), ['gmail.com', 'googlemail.com'])) {
            $result = strpos($this->getUsername(), '+') !== false;
        }

        return $result;
    }

    /**
     * Returns a gmail address without the "plus trick" portion of the email address.
     *
     * @since 1.1.0
     * @return string
     */
    public function getGmailAddressWithoutPlus(): string
    {
        return preg_replace('/^(.+?)(\+.+?)(@.+)/', '$1$3', $this->getEmailAddress());
    }

    /**
     * Returns a gmail address without the "plus trick" portion of the email address and all dots removed.
     *
     * @since 1.1.4
     * @return string
     */
    public function getSanitizedGmailAddress(): string
    {
        $email = new EmailAddress($this->getGmailAddressWithoutPlus());
        return sprintf(
            '%s@%s',
            str_replace('.', '', $email->getUsername()),
            $email->getDomain()
        );
    }
}
