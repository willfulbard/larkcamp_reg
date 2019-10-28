<?php
namespace Responses;

require 'JSON.php';

use Exception;
use Peridot\ObjectPath\ObjectPath;
use JsonSchema\Entity\JsonPointer;

class Payload
{
    public $payload;
    public $json;
    protected $schema;
    protected $json_parser;
    protected $validation_errors;

    public function __construct($payload)
    {
        if (!$payload) {
            throw new PayloadException('the payload cannot be empty');
        }

        $this->payload = $payload;

        $this->json_parser = new \JSON\JSON();

        try {
            $this->json = $this->json_parser->parse($this->payload);
        } catch (Exception $e) {
            throw new PayloadException('JSON error - ' . $e->getMessage());
        }
    }

    public function setSchema($schema): void {
        $this->schema = $schema;
    }

    public function validate(): bool {
        $validator = new \JsonSchema\Validator;

        $json = $this->json;
        $schema = $this->schema;
        $validator->validate($json, $schema);

        $valid = $validator->isValid();

        if (!$valid) {
            $this->validation_errors = $validator->getErrors();
        } else {
            $this->validation_errors = [];
        }

        return $valid;
    }

    public function getValidationErrors() {

        $validation_errors = [];

        // Add value to the error
        foreach ($this->validation_errors as $error) {
            $paths = new JsonPointer('#' . $error['pointer']);
            $value = $this->json;

            foreach ($paths->getPropertyPaths() as $key) {
                if (is_array($value)) {
                    $value = $value[$key];
                } else {
                    $value = $value->$key;
                }
            }

            $error['value'] = $value;
            $validation_errors[] = $error;
        }
        return $validation_errors;
    }
}

class PayloadException extends Exception {}
