<?php
namespace Mailer;
// Load Composer's autoloader
require '../vendor/autoload.php';

use Exception;

class Mailer
{

    protected $mail;

    public function __construct(array $config = [])
    {
        $dotenv = \Dotenv\Dotenv::create(__DIR__ . '/..');
        $dotenv->load();

        // Instantiation and passing `true` enables exceptions
        $this->mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        $config_keys = [
            'MAIL_HOST',
            'MAIL_USER',
            'MAIL_PASS',
            'MAIL_SEC',
            'MAIL_PORT',
            'MAIL_FROM_ADDRESS',
            'MAIL_FROM_NAME',
            'USE_SENDMAIL',
        ];

        foreach ($config_keys as $key) {
            $config[$key] = $_ENV[$key] ?? $config[$key];
        }

        if ($config['USE_SENDMAIL']) {
            $this->mail->isSendmail();
        } else {
            $this->mail->SMTPDebug = 0;                    // Enable verbose debug output
            $this->mail->isSMTP();                         // Set mailer to use SMTP
            $this->mail->SMTPAuth   = true;                // Enable SMTP authentication

            $this->mail->Host       = $config['MAIL_HOST'];  // Specify main and backup SMTP servers
            $this->mail->Username   = $config['MAIL_USER'];  // SMTP username
            $this->mail->Password   = $config['MAIL_PASS'];  // SMTP password
            $this->mail->SMTPSecure = $config['MAIL_SEC'];   // Enable TLS encryption, `ssl` also accepted
            $this->mail->Port       = $config['MAIL_PORT'];  // TCP port to connect to
        }

        $this->mail->setFrom($config['MAIL_FROM_ADDRESS'], $config['MAIL_FROM_NAME']);

    }

    public function send(array $config = [])
    {

        $mail = $this->mail;

        $config['to']   = $config['to'] ?? $_ENV['MAIL_TO_ADDRESS'] ?? $_ENV['MAIL_FROM_ADDRESS'];
        $config['name'] = $config['name'] ?? $_ENV['MAIL_TO_NAME'] ?? '';

        try {
            //Server settings

            $mail->addAddress($config['to'], $config['name']);

            // Content
            $mail->Subject = $config['subject']; // TODO: Add Name
            $mail->Body    = $config['body']; // TODO: Add Body in CSV format

            $mail->send();
            // Message has been sent
        } catch (PHPMailer\PHPMailer\Exception $e) {
            throw new Exception($mail->ErrorInfo);
        }

    }
}

?>
