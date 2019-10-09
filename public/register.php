<?php
// Load Composer's autoloader
require '../vendor/autoload.php';
require 'Mailer.php';
require 'JSON.php';

$dotenv = Dotenv\Dotenv::create(__DIR__ . '/..');
$dotenv->load();

foreach([
    'PDO_DSN',
    'PDO_USER',
    'PDO_PASS',
    'MAIL_HOST',
    'MAIL_FROM_ADDRESS',
    'MAIL_FROM_NAME',
    'MAIL_USER',
    'MAIL_PASS',
    'MAIL_SEC',
    'MAIL_PORT',
    'MAIL_TO_ADDRESS',
    'MAIL_TO_NAME',
] as $v) {
    if (!array_key_exists($v, $_ENV)) killme("$v not set in .env");
}

$pdo_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

/* Connect to DB */
try {
    $dbh = new PDO($_ENV['PDO_DSN'], $_ENV['PDO_USER'], $_ENV['PDO_PASS'], $pdo_options);
} catch (PDOException $e) {
    killme('Connection failed: ' . $e->getMessage());
}

$contents = file_get_contents('php://input');

if (!$contents) {
    killme('Could not read post data');
}

try {
    $json = new JSON\JSON();
    $results = $json->parse($contents);
} catch (Exception $e) {
    killme("JSON error - " . $e->getMessage());
}

// TODO: Validate data with schema here

try {
    $stmt = $dbh
        ->prepare('INSERT INTO lark_registrations (data) VALUES (?)')
        ->execute([$contents]);
} catch (PDOException $e) {
    killme('Insert failed: ' . $e->getMessage());
}

try {
    $mail = new Mailer\Mailer();

    $mail->send([
        'to'      => $_ENV['MAIL_TO_ADDRESS'],
        'subject' => 'Registration From', // TODO: Add Name
        'body'    => 'This is the message body', // TODO: Add Body in CSV format
    ]);
} catch (Exception $e) {
    killme("Message could not be sent. Mailer Error: {$e->getMessage()}");
}

function killme($str) {
    http_response_code(400);
    if ($_ENV['DEBUG']) {
        die($str);
    } else {
        // TODO: some sort of logger here
    }
    die();
}

?>
