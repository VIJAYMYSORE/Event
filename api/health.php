<?php
/**
 * Created by PhpStorm.
 * User: vmysore
 * Date: 10/4/15
 * Time: 5:44 PM
 */

class api_health {

    public function __construct() {
        echo "inside the api";
    }

    public static function find() {

        $health = 1;
        $result = model_health::load($health);
        $resultObj = new stdClass();
        $resultObj->result = $result;
        return $result;

    }	
}
