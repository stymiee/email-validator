<?php

namespace EmailValidator\Validator;

use EmailValidator\EmailAddress;
use EmailValidator\Policy;
use PHPUnit\Framework\TestCase;

class CustomValidatorTest extends TestCase
{
    public function testCustomValidator(): void
    {
        $validator = new ExampleDotComValidator(new Policy());
        self::assertTrue($validator->validate(new EmailAddress('user@example.com')));
        self::assertFalse($validator->validate(new EmailAddress('user@gmail.com')));
    }

    public function testCustomValidatorWithPolicy(): void
    {
        $policy = new Policy(['customSetting' => true]);
        $validator = new ExampleDotComValidator($policy);
        self::assertTrue($validator->validate(new EmailAddress('user@example.com')));
        self::assertFalse($validator->validate(new EmailAddress('user@gmail.com')));
    }

    public function testCustomValidatorWithEmailValidator(): void
    {
        $emailValidator = new \EmailValidator\EmailValidator();
        $emailValidator->registerValidator(new ExampleDotComValidator(new Policy()));
        
        self::assertTrue($emailValidator->validate('user@example.com'));
        self::assertFalse($emailValidator->validate('user@gmail.com'));
        self::assertEquals(\EmailValidator\EmailValidator::FAIL_CUSTOM, $emailValidator->getErrorCode());
        self::assertEquals('Failed custom validation', $emailValidator->getErrorReason());
    }
}

/**
 * Example custom validator that only allows example.com domains
 */
class ExampleDotComValidator extends AValidator
{
    public function validate(EmailAddress $email): bool
    {
        return $email->getDomain() === 'example.com';
    }
} 