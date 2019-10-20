<?php
namespace DBConn;

use PDO;

class Database extends PDO
{
    public function __construct()
    {
        $config_keys = [
            'PDO_DSN',
            'PDO_USER',
        ];

        foreach ($config_keys as $key) {
            if (!getenv($key)) throw new \PDOException("$key not set!");
        }

        $pdo_options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        /* Connect to DB */
        parent::__construct(getenv('PDO_DSN'), getenv('PDO_USER'), getenv('PDO_PASS'), $pdo_options);
    }
}

?>
