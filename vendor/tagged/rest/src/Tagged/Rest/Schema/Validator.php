<?php
namespace Tagged\Rest\Schema;
use Klein\Exceptions;

class Validator {
    private $schema;
    private $schemaObj;

    /**
     * A validator class that employs \Jsv4 to validate a php object
     * @param array $schema php assoc array represntation of a json schema.
     */
    public function __construct(array $schema) {
        $this->schema = $schema;
        $this->schemaObj = json_decode(json_encode($schema));
    }

    public function validate($data) {
        if (empty($data)) {
            $objData = new \stdClass();
        } else {
            $objData = json_decode(json_encode($data));
        }

        $result = \Jsv4::coerce($objData, $this->schemaObj);

        if (!$result->valid) {
            throw new \Exception($result->errors[0]->message, 400);
        }

        return $result->value;
    }

    public function getSchema() {
        return $this->schema;
    }
}

