<?php

declare(strict_types=1);

namespace EmailValidator\Validator;

use EmailValidator\EmailAddress;

class DisposableEmailValidator extends AValidator
{
    public function validate(EmailAddress $email): bool
    {
        $valid = true;
        if ($this->policy->checkDisposableEmail()) {
            $domain = $email->getDomain();
            return !in_array($domain, $this->policy->getDisposableList(), true);
        }
        return $valid;
    }
}
