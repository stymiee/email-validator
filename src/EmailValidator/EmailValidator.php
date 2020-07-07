<?php

declare(strict_types=1);

namespace EmailValidator;

use EmailValidator\Validator\BannedListValidator;
use EmailValidator\Validator\BasicValidator;
use EmailValidator\Validator\DisposableEmailValidator;
use EmailValidator\Validator\MxValidator;

class EmailValidator
{
    public const NO_ERROR = 0;

    public const FAIL_BASIC = 1;

    public const FAIL_MX_RECORD = 2;

    public const FAIL_BANNED_DOMAIN = 3;

    public const FAIL_DISPOSABLE_DOMAIN = 4;

    /**
     * @var BasicValidator
     */
    private $basicValidator;

    /**
     * @var MxValidator
     */
    private $mxValidator;

    /**
     * @var BannedListValidator
     */
    private $bannedListValidator;

    /**
     * @var DisposableEmailValidator
     */
    private $disposableEmailValidator;

    /**
     * @var int
     */
    private $reason;

    public function __construct(array $config = [])
    {
        $this->reason = self::NO_ERROR;

        $policy = new Policy($config);

        $this->basicValidator = new BasicValidator($policy);
        $this->mxValidator = new MxValidator($policy);
        $this->bannedListValidator = new BannedListValidator($policy);
        $this->disposableEmailValidator = new DisposableEmailValidator($policy);
    }

    /**
     * Validate an email address by the rules set forth in the Policy
     *
     * @param string $email
     * @return bool
     */
    public function validate(string $email): bool
    {
        $emailAddress = new EmailAddress($email);

        if (!$this->basicValidator->validate($emailAddress)) {
            $this->reason = self::FAIL_BASIC;
        } elseif (!$this->mxValidator->validate($emailAddress)) {
            $this->reason = self::FAIL_MX_RECORD;
        } elseif (!$this->bannedListValidator->validate($emailAddress)) {
            $this->reason = self::FAIL_BANNED_DOMAIN;
        } elseif (!$this->disposableEmailValidator->validate($emailAddress)) {
            $this->reason = self::FAIL_DISPOSABLE_DOMAIN;
        }

        return $this->reason === self::NO_ERROR;
    }

    public function getErrorReason(): string
    {
        switch ($this->reason)
        {
            case self::FAIL_BASIC :
                $msg = 'Invalid format';
                break;
            case self::FAIL_MX_RECORD :
                $msg = 'Domain does not accept email';
                break;
            case self::FAIL_BANNED_DOMAIN :
                $msg = 'Domain is banned';
                break;
            case self::FAIL_DISPOSABLE_DOMAIN :
                $msg = 'Domain is used by disposable email providers';
                break;
            default :
                $msg = '';
        }

        return $msg;
    }
}
