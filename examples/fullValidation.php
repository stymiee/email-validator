<?php

use EmailValidator\EmailValidator;

require('../vendor/autoload.php');

$customDisposableEmailList = [
    'example.com',
];

$bannedDomainList = [
    'domain.com',
];

$testEmailAddresses = [
    'test@johnconde.net',
    'test@gmail.com',
    'test@hotmail.com',
    'test@outlook.com',
    'test@yahoo.com',
    'test@domain.com',
    'test@example.com',
    'test@nobugmail.com',
    'test@mxfuel.com',
    'test@cellurl.com',
    'test@10minutemail.com',
];

$config = [
    'checkMxRecords' => true,
    'checkBannedListedEmail' => true,
    'checkDisposableEmail' => true,
    'checkFreeEmail' => true,
    'bannedList' => $bannedDomainList,
    'disposableList' => $customDisposableEmailList,
];
$emailValidator = new EmailValidator($config);

foreach ($testEmailAddresses as $emailAddress) {
    $emailIsValid = $emailValidator->validate($emailAddress);
    echo  ($emailIsValid) ? 'Email is valid' : $emailValidator->getErrorReason();
    echo PHP_EOL;
}
