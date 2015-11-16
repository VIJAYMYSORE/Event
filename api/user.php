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
        var_dump($_REQUEST);
        if(!isset($_REQUEST['userId']) || !is_numeric($_REQUEST['userId'])) {
            throw new Exception("UserId not set or invalid");
        }
        $id = $_REQUEST->userId;
        $result = model_user::load($id);
        $resultObj = new stdClass();
        $resultObj->result = $result;
        return $resultObj;

    }

    public static function create() {
        global $g_body;
        $firstName = $g_body->firstName;
        $lastName = $g_body->lastName;
        $emailId = $g_body->emailId;
        $facebookId = $g_body->facebookId;
        $dateOfBirth = $g_body->dateOfBirth;
        $result  = model_user::saveUser($firstName, $lastName, $emailId, $facebookId, $dateOfBirth);
        $resultObj = new stdClass();
        $resultObj->result = "Success";
        return $resultObj;
    }

}