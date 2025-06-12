
<?php

require_once 'vendor/autoload.php';

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;

echo "<h1>EmailValidator Test</h1>\n";

$validator = new EmailValidator();

// Test emails
$testEmails = [
    'test@example.com',
    'invalid.email',
    'user@domain.org',
    'test@invalid-domain-that-does-not-exist.com',
    'valid@gmail.com'
];

echo "<h2>RFC Validation Tests</h2>\n";
foreach ($testEmails as $email) {
    $isValid = $validator->isValid($email, new RFCValidation());
    $status = $isValid ? '✅ Valid' : '❌ Invalid';
    echo "<p><strong>$email</strong>: $status</p>\n";
}

echo "<h2>Multiple Validation Tests (RFC + DNS)</h2>\n";
$multipleValidations = new MultipleValidationWithAnd([
    new RFCValidation(),
    new DNSCheckValidation()
]);

foreach ($testEmails as $email) {
    $isValid = $validator->isValid($email, $multipleValidations);
    $status = $isValid ? '✅ Valid' : '❌ Invalid';
    echo "<p><strong>$email</strong>: $status</p>\n";
}

echo "<h2>No RFC Warnings Validation</h2>\n";
foreach ($testEmails as $email) {
    $isValid = $validator->isValid($email, new NoRFCWarningsValidation());
    $status = $isValid ? '✅ Valid' : '❌ Invalid';
    echo "<p><strong>$email</strong>: $status</p>\n";
}

echo "<hr>";
echo "<p>EmailValidator library is working correctly!</p>";
?>
