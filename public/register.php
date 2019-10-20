<?php
// Load Composer's autoloader
require '../vendor/autoload.php';

require_once 'php/Env.php';
require 'php/Mailer.php';
require 'php/DBConn.php';
require 'php/RegPayload.php';

try {
    $dbh = new DBConn\Database();
} catch (PDOException $e) {
    killme('Connection failed: ' . $e->getMessage());
}

try {
    $payload = new Responses\RegPayload(file_get_contents('php://input'));
} catch (Responses\PayloadException $e) {
    killme($e->getMessage());
}

if (!$payload->validate()) {
    $msg = "JSON does not validate. Violations:\n";
    foreach ($payload->getValidationErrors() as $error) {
        $msg .= sprintf("[%s] %s\n", $error['property'], $error['message']);
    }

    killme($msg);
}

try {
    $stmt = $dbh
        ->prepare('INSERT INTO lark_registrations (data) VALUES (?)')
        ->execute([$payload->payload]);
} catch (PDOException $e) {
    killme('Insert failed: ' . $e->getMessage());
}

try {
    $mail = new Mailer\Mailer();

    $mail->send([
        'to'      => getenv('MAIL_TO_ADDRESS'),
        'subject' => 'Registration From', // TODO: Add Name
        'body'    => 'This is the message body', // TODO: Add Body in CSV format
    ]);
} catch (Exception $e) {
    killme("Message could not be sent. Mailer Error: {$e->getMessage()}");
}

function killme($str) {
    http_response_code(400);
    if (getenv('DEBUG')) {
        die($str);
    } else {
        // TODO: some sort of logger here
    }
    die();
}

?>
