<?php
namespace Responses;

require 'JSON.php';

use Exception;

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

        $is_valid = $validator->isValid();

        if (!$is_valid) {
            $this->validation_errors = $validator->getErrors();
        } else {
            $this->validation_errors = [];
        }

        return $is_valid;
    }

    public function getValidationErrors() {
        return $this->validation_errors;
    }
}

class PayloadException extends Exception {}
