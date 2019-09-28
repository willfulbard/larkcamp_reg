<?php
// Load Composer's autoloader
require '../vendor/autoload.php';

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

$results = json_decode($contents);

$json_error = json_last_error();

if ($json_error !== JSON_ERROR_NONE) {
    $message = 'JSON error';
    switch ($json_error) {
        case JSON_ERROR_DEPTH:
            $message .= ' - Maximum stack depth exceeded';
        break;
        case JSON_ERROR_STATE_MISMATCH:
            $message .= ' - Underflow or the modes mismatch';
        break;
        case JSON_ERROR_CTRL_CHAR:
            $message .= ' - Unexpected control character found';
        break;
        case JSON_ERROR_SYNTAX:
            $message .= ' - Syntax error, malformed JSON';
        break;
        case JSON_ERROR_UTF8:
            $message .= ' - Malformed UTF-8 characters, possibly incorrectly encoded';
        break;
        default:
            $message .= ' - Unknown error';
        break;
    }

    killme($message);
}

// TODO: Validate data with schema here

try {
    $stmt = $dbh
        ->prepare('INSERT INTO lark_registrations (data) VALUES (?)')
        ->execute([$contents]);
} catch (PDOException $e) {
    killme('Insert failed: ' . $e->getMessage());
}


// Instantiation and passing `true` enables exceptions
$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    //Server settings

    if ($_ENV['USE_SENDMAIL']) {
        $mail->isSendmail();
    } else {
        $mail->SMTPDebug = 0;                    // Enable verbose debug output
        $mail->isSMTP();                         // Set mailer to use SMTP
        $mail->SMTPAuth   = true;                // Enable SMTP authentication

        $mail->Host       = $_ENV['MAIL_HOST'];  // Specify main and backup SMTP servers
        $mail->Username   = $_ENV['MAIL_USER'];  // SMTP username
        $mail->Password   = $_ENV['MAIL_PASS'];  // SMTP password
        $mail->SMTPSecure = $_ENV['MAIL_SEC'];   // Enable TLS encryption, `ssl` also accepted
        $mail->Port       = $_ENV['MAIL_PORT'];  // TCP port to connect to
    }

    //Recipients
    $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
    $mail->addAddress($_ENV['MAIL_TO_ADDRESS'], $_ENV['MAIL_TO_NAME']);

    // Content
    $mail->Subject = 'Registration From'; // TODO: Add Name
    $mail->Body    = 'This is the message body'; // TODO: Add Body in CSV format

    $mail->send();
    // Message has been sent
} catch (PHPMailer\PHPMailer\Exception $e) {
    killme("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
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
