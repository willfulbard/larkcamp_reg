<?php
namespace DBConn;
// Load Composer's autoloader
require '../vendor/autoload.php';

use PDO;

class Database extends PDO
{
    public function __construct()
    {
        $dotenv = \Dotenv\Dotenv::create(__DIR__ . '/../..');
        $dotenv->load();

        $config_keys = [
            'PDO_DSN',
            'PDO_USER',
            'PDO_PASS',
        ];

        foreach ($config_keys as $key) {
            $config[$key] = $_ENV[$key];
        }

        $pdo_options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        /* Connect to DB */
        parent::__construct($_ENV['PDO_DSN'], $_ENV['PDO_USER'], $_ENV['PDO_PASS'], $pdo_options);
    }
}

?>
