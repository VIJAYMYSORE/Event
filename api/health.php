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

        $health = $_REQUEST['health'];
        $result = model_health::load($health);
        return $result;

    }	
}
