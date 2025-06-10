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

    /**
     * @dataProvider invalidDomainLiteralEmailProvider
     */
    public function testInvalidDomainLiteralEmails(string $email): void
    {
        $this->assertFalse($this->validator->validate(new EmailAddress($email)));
    }

    public function invalidDomainLiteralEmailProvider(): array
    {
        return [
            'unclosed bracket' => ['user@[192.168.1.1'],
            'invalid IPv4 octet' => ['user@[192.168.1.300]'],
            'IPv4 with invalid chars' => ['user@[192.168.1.x]'],
            'IPv4 with extra dots' => ['user@[192.168.1.1.1]'],
            'IPv4 with missing octet' => ['user@[192.168.1]'],
            'IPv6 without prefix' => ['user@[2001:db8::1]'],
            'IPv6 with invalid hex' => ['user@[IPv6:2001:db8::g]'],
            'IPv6 with too many segments' => ['user@[IPv6:2001:db8:1:2:3:4:5:6:7]'],
            'empty domain literal' => ['user@[]'],
            'IPv6 with malformed compression' => ['user@[IPv6:2001:db8:::1]'],
            'IPv6 with triple compression' => ['user@[IPv6:2001::db8::1]'],
            'IPv6 with invalid split parts' => ['user@[IPv6:2001:::db8:1]'],
            'IPv6 with multiple double colons' => ['user@[IPv6:2001::db8::1::2]'],
            'IPv6 with empty segment' => ['user@[IPv6:2001::db8::]'],
            'IPv6 with empty segment after split' => ['user@[IPv6:2001:db8:::]'],
            'IPv6 with empty segment in middle' => ['user@[IPv6:2001::db8:0::1]'],
            'IPv6 with too many segments after compression' => ['user@[IPv6:2001:db8::1:2:3:4:5:6]'],
            'IPv6 with invalid segment length' => ['user@[IPv6:20011:db8::1]'],
            'IPv6 with invalid segment count' => ['user@[IPv6:2001:db8]'],
            'IPv6 with empty segment in array' => ['user@[IPv6:2001:db8::1::2]'],
            'IPv6 with empty segment after merge' => ['user@[IPv6:2001:db8::1:2:3:4:5:6:7]'],
            'IPv6 with too few segments' => ['user@[IPv6:2001:db8:1]'],
            'IPv6 with invalid compression' => ['user@[IPv6:2001:db8::1::2]'],
            'IPv6 with too many segments at start' => ['user@[IPv6::1:2:3:4:5:6:7]'],
            'IPv6 with invalid segment format' => ['user@[IPv6:2001:db8::1::]'],
            'IPv6 with compression at start' => ['user@[IPv6::1:2:3:4:5:6]'],
        ];
    }

    /**
     * @dataProvider emptyComponentsEmailProvider
     */
    public function testEmptyComponents(string $email): void
    {
        $emailObj = new EmailAddress($email);
        $this->assertFalse($this->validator->validate($emailObj));
    }

    public function emptyComponentsEmailProvider(): array
    {
        return [
            'null local part' => ['@example.com'],
            'null domain' => ['user@'],
            'both null' => ['@'],
            'empty string' => [''],
        ];
    }

    /**
     * @dataProvider quotedStringEdgeCasesProvider
     */
    public function testQuotedStringEdgeCases(string $email): void
    {
        $this->assertTrue($this->validator->validate(new EmailAddress($email)));
    }

    public function quotedStringEdgeCasesProvider(): array
    {
        return [
            'empty quoted string' => ['""@example.com'],
            'quoted with required escapes' => ['"test\\"\\\\test"@example.com'],
            'quoted with optional escapes' => ['"test\\@\\,\\;test"@example.com'],
            'quoted with mixed escapes' => ['"test\\"\\@\\\\test"@example.com'],
            'quoted with spaces' => ['" test test "@example.com'],
            'quoted with special chars' => ['"!#$%&\'*+-/=?^_`{|}~"@example.com'],
            'quoted with numbers' => ['"0123456789"@example.com'],
            'quoted with letters' => ['"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"@example.com'],
            'quoted with punctuation' => ['",.;:()[]{}<>@"@example.com'],
            'quoted with escaped non-required chars' => ['"\\a\\b\\c"@example.com'],
        ];
    }

    /**
     * @dataProvider ipv6EdgeCasesProvider
     */
    public function testIpv6EdgeCases(string $email): void
    {
        $this->assertTrue($this->validator->validate(new EmailAddress($email)));
    }

    public function ipv6EdgeCasesProvider(): array
    {
        return [
            'IPv6 with multiple zeros' => ['user@[IPv6:2001:0000:0000:0000:0000:0000:0000:0001]'],
            'IPv6 with single compression' => ['user@[IPv6:2001:db8::1]'],
            'IPv6 with leading zeros' => ['user@[IPv6:0:2001:db8:1:2:3:4:5]'],
            'IPv6 with trailing compression' => ['user@[IPv6:2001:db8::]'],
            'IPv6 with middle compression' => ['user@[IPv6:2001:db8::1:2]'],
            'IPv6 with minimal segments' => ['user@[IPv6:1:2:3:4:5:6:7:8]'],
            'IPv6 with compression and zeros' => ['user@[IPv6:2001:0db8::0001:0000:0000:0001]'],
            'IPv6 with mixed case hex' => ['user@[IPv6:2001:DbB8::1]'],
            'IPv6 with single digit segments' => ['user@[IPv6:1:2:3:4:5:6:7:8]'],
            'IPv6 with max segments' => ['user@[IPv6:2001:db8:85a3:8a2e:370:7334:1:1]'],
            'IPv6 with max compression' => ['user@[IPv6:2001:db8::8]'],
            'IPv6 with compression at end' => ['user@[IPv6:2001:db8:1:2:3:4:5::]'],
            'IPv6 with max zeros' => ['user@[IPv6:0:0:0:0:0:0:0:1]'],
        ];
    }

    /**
     * @dataProvider ipv4EdgeCasesProvider
     */
    public function testIpv4EdgeCases(string $email): void
    {
        $this->assertTrue($this->validator->validate(new EmailAddress($email)));
    }

    public function ipv4EdgeCasesProvider(): array
    {
        return [
            'IPv4 with leading zeros' => ['user@[192.168.001.001]'],
            'IPv4 with all zeros' => ['user@[000.000.000.000]'],
            'IPv4 with mixed zeros' => ['user@[192.168.000.001]'],
        ];
    }
}
