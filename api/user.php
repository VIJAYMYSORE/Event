<?php

/**
 * Created by PhpStorm.
 * User: vmysore
 * Date: 11/15/15
 * Time: 7:03 PM
 */
class api_user {

    public function __construct() {

    }

    public static function find() {
        if(!isset($_REQUEST->userId) || !is_numeric($_REQUEST->userId)) {
            throw new Exception("UserId not set or invalid");
        }
        $id = $_REQUEST->userId;
        $result = model_user::load($id);
        $resultObj = new stdClass();
        $resultObj->result = $result;
        return $resultObj;

    }

    public static function create() {
        $firstName = $_REQUEST->firstName;
        $lastName = $_REQUEST->lastName;
        $emailId = $_REQUEST->emailId;
        $facebookId = $_REQUEST->facebookId;
        $dateOfBirth = $_REQUEST->dateOfBirth;
        $result  = model_user::saveUser($firstName, $lastName, $emailId, $facebookId, $dateOfBirth);
        $resultObj = new stdClass();
        $resultObj->result = "Success";
        return $resultObj;
    }

}