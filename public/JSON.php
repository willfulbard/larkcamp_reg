<?php
namespace JSON;
// Load Composer's autoloader
require '../vendor/autoload.php';

use Exception;

class JSON
{

    public function parse(string $str)
    {

        $results = json_decode($str);

        $json_error = json_last_error();

        if ($json_error !== JSON_ERROR_NONE) {
            $message = '';
            switch ($json_error) {
            case JSON_ERROR_DEPTH:
                $message = 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $message = 'Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $message = 'Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $message = 'Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                $message = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $message = 'Unknown error';
                break;
            }

            throw new Exception($message);
        }

        return $results;
    }
}

?>
