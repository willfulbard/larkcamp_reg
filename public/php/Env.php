<?php
use Dotenv\Dotenv;

function read_env_file() {
    $filename = '.env';

    if (getenv('ENV_FILE')) $filename = getenv('ENV_FILE');

    $dotenv = Dotenv::create(__DIR__ . "/../../", $filename);

    $dotenv->load();

}

read_env_file();
