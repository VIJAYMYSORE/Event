<?php

/**
 * Created by PhpStorm.
 * User: vmysore
 * Date: 11/15/15
 * Time: 7:20 PM
 */
class model_user {

    public $userId;
    public $firstName;
    public $lastName;
    public $emailId;
    public $facebookId;
    public $dateOfBirth;
    public $dateModified;

    const LOAD_SQL = "SELECT * FROM `User` where id = ";

    const SAVE_SQL = "INSERT INTO `User` (`firstName`, `lastName`, `emailId`, `facebookId`, `dateOfBirth`) values (";



    public function __construct($userId, $firstName, $lastName, $emailId, $facebookId, $dateOfBirth) {
        $this->userId = $userId;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->emailId = $emailId;
        $this->facebookId = $facebookId;
        $this->dateOfBirth = $dateOfBirth;
    }

    public static function load($id) {

        $dbConnection = db_base::GetDBConnection();
        $result = $dbConnection->execSQLStmt(self::LOAD_SQL . $id);
        $row = (object)$result[0];
        return $row;
    }

    public static function saveUser($firstName, $lastName, $emailId, $facebookId, $dateOfBirth) {
        $dbConnection = db_base::GetDBConnection();
        $dbConnection->execSQLStmt(self::SAVE_SQL . "'$firstName', '$lastName', '$emailId', '$facebookId', '$dateOfBirth')");
        $id = $dbConnection->last_insert_id();
        $result['id'] = $id;
        return $result;
    }
}