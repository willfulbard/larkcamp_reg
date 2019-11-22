<?php
namespace LarkRegistration\Tests;

require_once 'public/php/Env.php';
require_once 'public/php/JSON.php';
require_once 'public/php/DBConn.php';
require_once 'public/php/RegPayload.php';

use PHPUnit\Framework\TestCase;
use GuzzleHttp;
use GuzzleHttp\Exception\RequestException;
use PDO;
use Symfony\Component\Process\Process;

class RegistrationSubmissionTest extends TestCase
{
    protected $http;
    protected $dbh;
    private static $process;
    private static $host;

    public static function setUpBeforeClass(): void
    {
        self::$host = 'localhost:4002';
        self::$process = new Process([
            'php',
            '-S', self::$host,
            // fake sendmail
            '-d', 'sendmail_path='.getcwd().'/php-tests/fakesendmail.sh',
            '-t', 'public/',
        ]);
        self::$process->enableOutput();
        self::$process->start();

        sleep(1);
    }

    public static function tearDownAfterClass(): void
    {
        self::$process->stop();
    }

    public function setUp(): void
    {
        $this->http = new GuzzleHttp\Client([
            'base_uri' => self::$host,
            'http_errors' => false,
        ]);

        try {
            $this->http->get('/register.php', [
                'timeout'  => 0.5,
            ]);
        } catch (RequestException $e) {
            die(<<<EOF
#############
Could not connect to server after 500ms!
Are you sure the test server started?
Try `yarn php` to start the server and look for errors.

EOF
            );
        }

        $pdo_options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        /* Connect to DB */


        $this->dbh = new \DBConn\Database();
    }

    public function tearDown(): void 
    {
        echo self::$process->getOutput();
    }

    public function testBadSubmit()
    {
        $response = $this->http->post('/register.php');

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('the payload cannot be empty', (string) $response->getBody());
    }

    public function testJsonErrors()
    {
        $json_responses = [
            '{bad json'    => [400, 'Syntax error, malformed JSON'],
            "\"\0\""       => [400, 'Unexpected control character found'],
            "\"\xB1\x31\"" => [400, 'Malformed UTF-8 characters, possibly incorrectly encoded'],
        ];

        foreach($json_responses as $body => $res) {
            $response = $this->http->post('/register.php', [
                'body' => $body,
            ]);

            $this->assertEquals('JSON error - ' . $res[1], (string) $response->getBody());
            $this->assertEquals($res[0], $response->getStatusCode());
        }
    }

    public function formatSchemaError($payload)
    {
        $msg = "json failed to validate:\n";

        foreach ($payload->getValidationErrors() as $error) {
            $msg .= sprintf("[%s = %s] %s\n", $error['property'], $error['value'], $error['message']);
        }

        return $msg;
    }

    public function testSchemaRobustness()
    {
        // Try to validate 50 random responses.  Although not deterministic,
        // this will probably weed out most fragile schema components
        for($i = 0; $i < 50; $i++) {
            $json_body = exec(getcwd() . '/php-tests/generate-random-reg.js');

            $payload = new \Responses\RegPayload($json_body);

            $this->assertEquals(
                $payload->validate(),
                true, 
                $this->formatSchemaError($payload)
            );
        }
    }

    public function testGoodSubmit()
    {
        $cwd = getcwd();
        $json_body = exec("$cwd/php-tests/generate-random-reg.js");

        // check posting
        $response = $this->http->post('/register.php', [
            'body' => $json_body,
        ]);

        $this->assertEquals('Registration successful', (string) $response->getBody(), "Failed: " . $json_body);
        $this->assertEquals(200, $response->getStatusCode());

        // check that it wrote to the DB
        $stmt = $this->dbh->prepare(
            'SELECT data FROM lark_registrations ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute();
        $data = $stmt->fetch()['data'];

        $json_parser = new \JSON\JSON();
        $data_obj = $json_parser->parse($data);
        $body_obj = $json_parser->parse($json_body);

        $this->assertEquals($body_obj, $data_obj);

        // Test Email
        $contents = '';
        $i = 0;
        while (!$contents && $i < 5) {
            $contents = file_get_contents("$cwd/php-tests/emails/message");
            $i++;
            sleep(1);
        }
        $this->assertNotEquals("", $contents, 'Email did not send');


        $raw_email_json = exec("$cwd/php-tests/read-test-email.js");

        $email = $json_parser->parse($raw_email_json);

        $this->assertEquals($email->to->value[0]->name, $_ENV['MAIL_TO_NAME'], 'MailTo name is incorrect');
        $this->assertEquals($email->to->value[0]->address, $_ENV['MAIL_TO_ADDRESS'], 'MailTo address is incorrect');

        $this->assertEquals($email->from->value[0]->name, $_ENV['MAIL_FROM_NAME'], 'MailFrom name is incorrect');
        $this->assertEquals($email->from->value[0]->address, $_ENV['MAIL_FROM_ADDRESS'], 'MailFrom address is incorrect');

        $this->assertEquals(
            $email->subject,
            'Registration From ' . $body_obj->payer_first_name . ' ' . $body_obj->payer_last_name,
            'Mail subject is incorrect'
        );

        $this->assertStringContainsString(
            "\"{$body_obj->campers[0]->first_name}\"",
            $email->text,
            'email message did not contain the campers first name'
        );

        $this->assertStringContainsString(
            "\"{$body_obj->campers[0]->last_name}\"",
            $email->text,
            'email message did not contain the campers last name'
        );
    }
}
