<?php

declare(strict_types=1);

namespace EmailValidator\Validator;

use EmailValidator\EmailAddress;

class DisposableEmailValidator extends AProviderValidator
{
    /**
     * @var array<string> Array of client-provided disposable email providers.
     */
    protected array $disposableEmailListProviders = [];

    /**
     * @var array<array{format: string, url: string}> Array of URLs containing a list of disposable email addresses and the format of that list.
     */
    protected static array $providers = [
        [
            'format' => 'txt',
            'url' => 'https://raw.githubusercontent.com/martenson/disposable-email-domains/master/disposable_email_blocklist.conf'
        ],
        [
            'format' => 'json',
            'url' => 'https://raw.githubusercontent.com/ivolo/disposable-email-domains/master/wildcard.json'
        ],
    ];

    /**
     * Checks to see if validating against disposable domains is enabled. If so, gets the list of disposable domains
     * and checks if the domain is one of them.
     *
     * @param EmailAddress $email
     * @return bool
     */
    public function validate(EmailAddress $email): bool
    {
        if (!$this->policy->checkDisposableEmail()) {
            return true;
        }

        if ($this->disposableEmailListProviders === []) {
            $this->disposableEmailListProviders = $this->getList(
                $this->policy->checkDisposableLocalListOnly(),
                $this->policy->getDisposableList()
            );
        }

        $domain = $email->getDomain();
        if ($domain === null) {
            return true;
        }

        return !in_array($domain, $this->disposableEmailListProviders, true);
    }
}
