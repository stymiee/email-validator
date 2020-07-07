<?php

declare(strict_types=1);

namespace EmailValidator\Validator;

use EmailValidator\EmailAddress;

class BasicValidator extends AValidator
{
    public function validate(EmailAddress $email): bool
    {
        return filter_var($email->getEmailAddress(), FILTER_VALIDATE_EMAIL);
    }
}
