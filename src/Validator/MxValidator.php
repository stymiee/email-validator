<?php

declare(strict_types=1);

namespace EmailValidator\Validator;

use EmailValidator\EmailAddress;

class MxValidator extends AValidator
{
    public function validate(EmailAddress $email): bool
    {
        $valid = true;
        if ($this->policy->validateMxRecord()) {
            $domain = $email->getDomain();
            $valid = checkdnsrr(idn_to_ascii($domain), 'MX');
        }
        return $valid;
    }
}
