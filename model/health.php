<?php

/**
 * Created by PhpStorm.
 * User: vmysore
 * Date: 10/12/15
 * Time: 4:36 PM
 */
class model_health {

    public $health;
    public $test1;
    public $test2;

    const LOAD_SQL = "SELECT * FROM `health`";



    public function __construct($health, $test1, $test2) {
        $this->health = $health;
        $this->test1 = $test1;
        $this->test2 = $test2;

    }

    public static function load($health) {

        $dbConnection = db_base::GetDBConnection();
        $result = $dbConnection->execSQLStmt(self::LOAD_SQL . " WHERE health = $health");
        return $result;
    }

}