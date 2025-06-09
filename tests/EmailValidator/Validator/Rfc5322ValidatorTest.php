<?php

namespace EmailValidator\Tests\Validator;

use EmailValidator\EmailAddress;
use EmailValidator\Policy;
use EmailValidator\Validator\Rfc5322Validator;
use PHPUnit\Framework\TestCase;

class Rfc5322ValidatorTest extends TestCase
{
    private Rfc5322Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Rfc5322Validator(new Policy());
    }

    /**
     * @dataProvider validEmailProvider
     */
    public function testValidEmails(string $email): void
    {
        $this->assertTrue($this->validator->validate(new EmailAddress($email)));
    }

    /**
     * @dataProvider invalidEmailProvider
     */
    public function testInvalidEmails(string $email): void
    {
        $this->assertFalse($this->validator->validate(new EmailAddress($email)));
    }

    public function validEmailProvider(): array
    {
        return [
            'simple' => ['user@example.com'],
            'with numbers' => ['user123@example.com'],
            'with allowed special chars' => ['user.name+tag@example.com'],
            'with hyphen in domain' => ['user@my-example.com'],
            'with subdomain' => ['user@sub.example.com'],
            'quoted string' => ['"John Doe"@example.com'],
            'quoted with special chars' => ['"john.doe"@example.com'],
            'escaped quotes' => ['"john\"doe"@example.com'],
            'local part with dots' => ['john.doe@example.com'],
            'local part with plus' => ['john+doe@example.com'],
            'local part with underscore' => ['john_doe@example.com'],
            'IPv4 literal' => ['user@[192.168.1.1]'],
            'IPv6 literal' => ['user@[IPv6:2001:db8::1]'],
            'with multiple dots' => ['john.doe.smith@example.com'],
            'with multiple subdomains' => ['user@one.two.three.example.com'],
            'with all allowed special chars' => ['!#$%&\'*+-/=?^_`{|}~@example.com'],
            'quoted with spaces' => ['"John Doe"@example.com'],
            'quoted with special chars 2' => ['"john(comment)doe"@example.com'],
            'quoted with at symbol' => ['"user@name"@example.com'],
            'quoted with escaped backslash' => ['"john\\\\doe"@example.com'],
        ];
    }

    public function invalidEmailProvider(): array
    {
        return [
            'empty string' => [''],
            'no at symbol' => ['userexample.com'],
            'multiple at symbols' => ['user@domain@example.com'],
            'empty local part' => ['@example.com'],
            'empty domain' => ['user@'],
            'single label domain' => ['user@localhost'],
            'invalid domain chars' => ['user@exam&ple.com'],
            'domain starts with hyphen' => ['user@-example.com'],
            'domain ends with hyphen' => ['user@example-.com'],
            'consecutive dots in local part' => ['john..doe@example.com'],
            'leading dot in local part' => ['.john.doe@example.com'],
            'trailing dot in local part' => ['john.doe.@example.com'],
            'unescaped quote in quoted string' => ['"john"doe"@example.com'],
            'unclosed quote' => ['"john doe@example.com'],
            'invalid IPv4 literal' => ['user@[256.256.256.256]'],
            'invalid IPv6 literal' => ['user@[IPv6:2001:db8:::1]'],
            'local part too long' => [str_repeat('a', 65) . '@example.com'],
            'domain too long' => ['user@' . str_repeat('a', 256) . '.com'],
            'domain label too long' => ['user@' . str_repeat('a', 64) . '.com'],
            'invalid chars in local part' => ['user[123]@example.com'],
            'missing domain' => ['user@'],
            'missing local part' => ['@example.com'],
            'invalid domain format' => ['user@.com'],
            'consecutive hyphens in domain' => ['user@exam--ple.com'],
            'unescaped backslash in quoted string' => ['"john\doe"@example.com'],
        ];
    }

    /**
     * @dataProvider commentEmailProvider
     */
    public function testEmailsWithComments(string $email, array $expectedComments): void
    {
        $emailObj = new EmailAddress($email);
        $this->assertTrue($this->validator->validate($emailObj));
        $this->assertEquals($expectedComments, $emailObj->getComments());
    }

    public function commentEmailProvider(): array
    {
        return [
            'simple comment' => [
                'user(comment)@example.com',
                ['comment']
            ],
            'multiple comments' => [
                'user(comment1)@(comment2)example.com',
                ['comment1', 'comment2']
            ],
            'nested comments' => [
                'user(outer(inner)comment)@example.com',
                ['outer(inner)comment']
            ],
            'escaped parentheses' => [
                'user(comment with \(escaped\) parentheses)@example.com',
                ['comment with \(escaped\) parentheses']
            ],
            'comment with special chars' => [
                'user(comment@with.special&chars)@example.com',
                ['comment@with.special&chars']
            ],
        ];
    }

    /**
     * @dataProvider quotedStringEmailProvider
     */
    public function testQuotedStringEmails(string $email): void
    {
        $this->assertTrue($this->validator->validate(new EmailAddress($email)));
    }

    public function quotedStringEmailProvider(): array
    {
        return [
            'simple quoted' => ['"John Doe"@example.com'],
            'quoted with @' => ['"user@name"@example.com'],
            'quoted with escaped quote' => ['"John \"The Dog\" Doe"@example.com'],
            'quoted with special chars' => ['"!#$%&\'*+-/=?^_`{|}~"@example.com'],
            'quoted with escaped backslash' => ['"John \\\\ Doe"@example.com'],
        ];
    }

    /**
     * @dataProvider domainLiteralEmailProvider
     */
    public function testDomainLiteralEmails(string $email): void
    {
        $this->assertTrue($this->validator->validate(new EmailAddress($email)));
    }

    public function domainLiteralEmailProvider(): array
    {
        return [
            'IPv4' => ['user@[192.168.1.1]'],
            'IPv6' => ['user@[IPv6:2001:db8::1]'],
            'IPv4 with leading zeros' => ['user@[192.168.001.001]'],
            'IPv6 full' => ['user@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:7334]'],
            'IPv6 compressed' => ['user@[IPv6:2001:db8::1:0:0:1]'],
        ];
    }
}
