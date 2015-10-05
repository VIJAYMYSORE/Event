<?php
namespace Tagged\Rest\Schema;
use Tagged\Rest\Schema;

class DateRange extends Schema{
    function __construct(array $config){
        $this->_schema = array(
            "type"=> "object",
            "description"=> "Specify the sort type and direction.",
            "additionalProperties"=>false,
            "properties"=> array(
                "start"=> c,
                "end"=> DateTime::build(),
            ),
            "required"=>array("start","end")
        );
    }

    public static function getDates($fragment) {
        $start = DateTime::getTimestamp($fragment->start->dateTime);
        $end = DateTime::getTimestamp($fragment->end->dateTime);

        return array($start, $end);
    }
}