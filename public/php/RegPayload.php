<?php
namespace Responses;

require 'Payload.php';

class RegPayload extends Payload
{
    public function validate(): bool {
        $config = $this->json_parser->parse(file_get_contents(__DIR__ . '/../config.json'));
        $schema = $config->dataSchema;

        $this->setSchema($schema);

        return parent::validate($schema);
    }

    public function toCSV(): string {
        // TODO: use the json-to-csv.json file to convert this to a CSV string
        return '';
    }
}
