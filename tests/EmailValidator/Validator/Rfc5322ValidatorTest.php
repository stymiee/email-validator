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
            'empty quoted string with space' => ['" "@example.com'],
            'domain literal with spaces' => ['user@[ 192.168.1.1 ]'],
            'IPv6 with lowercase prefix' => ['user@[ipv6:2001:db8::1]'],
            'domain with numeric TLD' => ['user@example.123'],
            'quoted with required escapes' => ['"test\\"\\\\test"@example.com'],
            'quoted with optional escapes' => ['"test\\@\\,\\;test"@example.com'],
            'quoted with mixed escapes' => ['"test\\"\\@\\\\test"@example.com'],
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
            'invalid domain format' => ['user@.com'],
            'consecutive hyphens in domain' => ['user@exam--ple.com'],
            'unescaped quote' => ['"test"test"@example.com'],
            'quoted string ending with backslash' => ['"test\\"\\@example.com'],
            'non-printable char in quoted string' => ['"test' . chr(1) . 'test"@example.com'],
            'malformed IPv6 literal' => ['user@[IPv6:1:2:3:4:5:6:7:8:9]'],
            'IPv6 with invalid chars' => ['user@[IPv6:xyz1:db8::1]'],
            'domain literal without content' => ['user@[]'],
            'domain with consecutive dots' => ['user@example..com'],
            'IPv6 with empty segment' => ['user@[IPv6:2001::db8::1]'],
            'IPv6 with too many compressed segments' => ['user@[IPv6:2001::db8::1::2]'],
            'IPv6 with invalid segment count' => ['user@[IPv6:2001:db8]'],
            'IPv6 with invalid segment length' => ['user@[IPv6:20011:db8::1]'],
            'IPv4 with invalid segment value' => ['user@[192.168.256.1]'],
        ];
    }

    /**
     * @dataProvider localPartLengthProvider
     */
    public function testLocalPartLength(string $localPart, bool $expected): void
    {
        $email = $localPart . '@example.com';
        $this->assertEquals($expected, $this->validator->validate(new EmailAddress($email)));
    }

    public function localPartLengthProvider(): array
    {
        return [
            'valid length' => ['user', true],
            'max length' => [str_repeat('a', 64), true],
            'too long' => [str_repeat('a', 65), false],
            'empty' => ['', false],
        ];
    }

    /**
     * @dataProvider atomValidationProvider
     */
    public function testAtomValidation(string $atom, bool $expected): void
    {
        $email = $atom . '@example.com';
        $this->assertEquals($expected, $this->validator->validate(new EmailAddress($email)));
    }

    public function atomValidationProvider(): array
    {
        return [
            'simple atom' => ['user', true],
            'with numbers' => ['user123', true],
            'with allowed special chars' => ['user+tag', true],
            'with all allowed special chars' => ['!#$%&\'*+-/=?^_`{|}~', true],
            'empty atom' => ['', false],
            'invalid chars' => ['user[123]', false],
            'with spaces' => ['user name', false],
            'with angle brackets' => ['user<>name', false],
        ];
    }

    /**
     * @dataProvider quotedStringValidationProvider
     */
    public function testQuotedStringValidation(string $quotedString, bool $expected): void
    {
        $email = $quotedString . '@example.com';
        $this->assertEquals($expected, $this->validator->validate(new EmailAddress($email)));
    }

    public function quotedStringValidationProvider(): array
    {
        return [
            'simple quoted' => ['"John Doe"', true],
            'empty quoted' => ['""', true],
            'quoted with space' => ['" "', true],
            'escaped quotes' => ['"John \"The Dog\" Doe"', true],
            'escaped backslash' => ['"John \\\\ Doe"', true],
            'with special chars' => ['"!#$%&\'*+-/=?^_`{|}~"', true],
            'unclosed quote' => ['"John Doe', false],
            'unescaped quote' => ['"John"Doe"', false],
            'ending with backslash' => ['"John\\', false],
            'non-printable char' => ['"test' . chr(1) . 'test"', false],
        ];
    }

    /**
     * @dataProvider domainLabelValidationProvider
     */
    public function testDomainLabelValidation(string $label, bool $expected): void
    {
        $reflectionClass = new \ReflectionClass(Rfc5322Validator::class);
        $method = $reflectionClass->getMethod('validateDomainLabel');
        $method->setAccessible(true);
        
        $this->assertEquals($expected, $method->invoke($this->validator, $label));
    }

    public function domainLabelValidationProvider(): array
    {
        return [
            'simple label' => ['example', true],
            'with numbers' => ['example123', true],
            'with hyphen' => ['my-example', true],
            'max length' => [str_repeat('a', 63), true],
            'too long' => [str_repeat('a', 64), false],
            'empty label' => ['', false],
            'starts with hyphen' => ['-example', false],
            'ends with hyphen' => ['example-', false],
            'consecutive hyphens' => ['exa--mple', false],
            'invalid chars' => ['exam&ple', false],
        ];
    }

    /**
     * @dataProvider domainLiteralValidationProvider
     */
    public function testDomainLiteralValidation(string $domainLiteral, bool $expected): void
    {
        $email = 'user@' . $domainLiteral;
        $this->assertEquals($expected, $this->validator->validate(new EmailAddress($email)));
    }

    public function domainLiteralValidationProvider(): array
    {
        return [
            'IPv4' => ['[192.168.1.1]', true],
            'IPv4 with spaces' => ['[ 192.168.1.1 ]', true],
            'IPv4 with leading zeros' => ['[192.168.001.001]', true],
            'IPv6' => ['[IPv6:2001:db8::1]', true],
            'IPv6 full' => ['[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:7334]', true],
            'IPv6 compressed' => ['[IPv6:2001:db8::1:0:0:1]', true],
            'unclosed bracket' => ['[192.168.1.1', false],
            'invalid IPv4 octet' => ['[192.168.1.300]', false],
            'IPv4 with invalid chars' => ['[192.168.1.x]', false],
            'IPv4 with missing octet' => ['[192.168.1]', false],
            'IPv6 without prefix' => ['[2001:db8::1]', false],
            'IPv6 with invalid hex' => ['[IPv6:2001:db8::g]', false],
            'empty domain literal' => ['[]', false],
        ];
    }

    /**
     * @dataProvider ipv6ValidationProvider
     */
    public function testIPv6Validation(string $ipv6, bool $expected): void
    {
        $email = 'user@[IPv6:' . $ipv6 . ']';
        $this->assertEquals($expected, $this->validator->validate(new EmailAddress($email)));
    }

    public function ipv6ValidationProvider(): array
    {
        return [
            'standard format' => ['2001:db8::1', true],
            'full format' => ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', true],
            'compressed middle' => ['2001:db8::1:0:0:1', true],
            'compressed end' => ['2001:db8:85a3::', true],
            'compressed start' => ['::1:2:3:4:5:6:7', true],
            'multiple zeros' => ['2001:0:0:1::2', true],
            'invalid segment count' => ['2001:db8', false],
            'too many segments' => ['2001:db8:1:2:3:4:5:6:7', false],
            'invalid hex' => ['2001:db8::g', false],
            'multiple compression' => ['2001::db8::1', false],
            'empty segment' => ['2001::db8::', false],
            'invalid segment length' => ['20011:db8::1', false],
        ];
    }

    /**
     * @dataProvider ipv4ValidationProvider
     */
    public function testIPv4Validation(string $ipv4, bool $expected): void
    {
        $email = 'user@[' . $ipv4 . ']';
        $this->assertEquals($expected, $this->validator->validate(new EmailAddress($email)));
    }

    public function ipv4ValidationProvider(): array
    {
        return [
            'standard format' => ['192.168.1.1', true],
            'zeros' => ['0.0.0.0', true],
            'max values' => ['255.255.255.255', true],
            'leading zeros' => ['192.168.001.001', true],
            'invalid octet' => ['256.256.256.256', false],
            'missing octet' => ['192.168.1', false],
            'extra octet' => ['192.168.1.1.1', false],
            'invalid chars' => ['192.168.1.x', false],
            'empty octet' => ['192.168..1', false],
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
}
