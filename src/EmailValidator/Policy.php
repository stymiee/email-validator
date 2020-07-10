<?php

declare(strict_types=1);

namespace EmailValidator;

class Policy
{
    /**
     * @var bool
     */
    private $checkBannedListedEmail;

    /**
     * @var bool
     */
    private $checkDisposableEmail;

    /**
     * @var array
     */
    private $bannedList;

    /**
     * @var bool
     */
    private $checkMxRecords;

    /**
     * @var array|mixed
     */
    private $disposableList;

    public function __construct(array $config = [])
    {
        $this->checkMxRecords         = (bool) ($config['checkMxRecords']         ?? false);
        $this->checkBannedListedEmail = (bool) ($config['checkBannedListedEmail'] ?? false);
        $this->checkDisposableEmail   = (bool) ($config['checkDisposableEmail']   ?? false);

        $this->bannedList             = $config['bannedList']                    ?? [];
        $this->disposableList         = $config['disposableList']                ?? [];
    }

    /**
     * Validate MX records?
     *
     * @return bool
     */
    public function validateMxRecord(): bool
    {
        return $this->checkMxRecords;
    }

    /**
     * Check domain if it is on the banned list?
     *
     * @return bool
     */
    public function checkBannedListedEmail(): bool
    {
        return $this->checkBannedListedEmail;
    }

    /**
     * Check if the domain is used by a disposable email site?
     *
     * @return bool
     */
    public function checkDisposableEmail(): bool
    {
        return $this->checkDisposableEmail;
    }

    /**
     * Returns the list of banned domains.
     *
     * @return array
     */
    public function getBannedList(): array
    {
        return $this->bannedList;
    }

    /**
     * Returns the list of disposable email domains.
     *
     * @return array
     */
    public function getDisposableList(): array
    {
        return $this->disposableList;
    }
}
