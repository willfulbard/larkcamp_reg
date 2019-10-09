<?php
namespace LarkRegistration\Tests;

use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;
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

        $dotenv = Dotenv::create(__DIR__ . '/..');
        $dotenv->load();

        $pdo_options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        /* Connect to DB */
        $this->dbh = new PDO($_ENV['PDO_DSN'], $_ENV['PDO_USER'], $_ENV['PDO_PASS'], $pdo_options);
    }

    public function tearDown(): void 
    {
        echo self::$process->getOutput();
    }

    public function testBadSubmit()
    {
        $response = $this->http->post('/register.php');

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Could not read post data', (string) $response->getBody());
    }

    public function testJSONErrors()
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

    public function testGoodSubmit()
    {
        $json_body = exec(getcwd() . '/php-tests/generate-random-reg.js');

        // check posting
        $response = $this->http->post('/register.php', [
            'body' => $json_body,
        ]);

        $this->assertEquals('', (string) $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());

        // check that it wrote to the DB
        $stmt = $this->dbh->prepare(
            'SELECT data FROM lark_registrations ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute();
        $data = $stmt->fetch()['data'];

        $this->assertEquals($json_body, $data);

        // check that it sent mail
        $email_assertions = [
            "To: {$_ENV['MAIL_TO_NAME']} <{$_ENV['MAIL_TO_ADDRESS']}>"
                => 'email message was not to the correct person',
            "From: {$_ENV['MAIL_FROM_NAME']} <{$_ENV['MAIL_FROM_ADDRESS']}>"
                => 'email message was not from the correct person',
            "Subject: Registration From"
                => 'email message did not have the correct subject',
        ];

        $cwd = getcwd();
        $contents = '';
        while (!$contents) {
            $contents = file_get_contents("$cwd/php-tests/emails/message");
        }

        foreach($email_assertions as $regex => $msg) {
            $this->assertRegExp("/$regex/", $contents, $msg);
        }
    }
}
