<?php

declare(strict_types=1);

namespace EmailValidator\Validator;

use EmailValidator\EmailAddress;

class DisposableEmailValidator extends AValidator
{
    /**
     * @var array Array of URLs containing a list of disposable email addresses and the format of that list.
     */
    private static $disposableEmailListProviders = [
        [
            'format' => 'txt',
            'url' => 'https://raw.githubusercontent.com/martenson/disposable-email-domains/master/disposable_email_blocklist.conf'
        ],
        [
            'format' => 'txt',
            'url' => 'https://gist.githubusercontent.com/michenriksen/8710649/raw/e09ee253960ec1ff0add4f92b62616ebbe24ab87/disposable-email-provider-domains'
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
        $valid = true;
        if ($this->policy->checkDisposableEmail()) {
            static $disposableEmailListProviders;
            if ($disposableEmailListProviders === null) {
                $disposableEmailListProviders = $this->getDisposableEmailList();
            }
            $domain = $email->getDomain();
            $valid = !in_array($domain, $disposableEmailListProviders, true);
        }
        return $valid;
    }

    /**
     * Gets public lists of disposable email address domains and merges them together into one array. If a custom
     * list is provided it is merged into the new list.
     *
     * @return array
     */
    public function getDisposableEmailList(): array
    {
        $providers = [];
        if (!$this->policy->checkDisposableLocalListOnly()) {
            foreach (self::$disposableEmailListProviders as $provider) {
                if (filter_var($provider['url'], FILTER_VALIDATE_URL)) {
                    $content = @file_get_contents($provider['url']);
                    if ($content) {
                        $providers[] = $this->getExternalList($content, $provider['format']);
                    }
                }
            }
        }
        return array_filter(array_unique(array_merge($this->policy->getDisposableList(), ...$providers)));
    }

    /**
     * Parses a list of disposable email address domains based on their format.
     *
     * @param string $content
     * @param string $type
     * @return array
     */
    private function getExternalList(string $content, string $type): array
    {
        switch ($type) {
            case 'json':
                $providers = json_decode($content, true);
                break;
            case 'txt':
            default:
                $providers = explode("\n", str_replace("\r\n", "\n", $content));
                break;
        }
        return $providers;
    }
}
