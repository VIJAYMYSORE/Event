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

    public static function find($request) {
        if(!isset($request['userId']) || !is_numeric($request['userId'])) {
            throw new Exception("UserId not set or invalid");
        }
        $id = $request['userId'];
        $result = model_user::load($id);
        $resultObj = new stdClass();
        $resultObj->result = $result;
        return $resultObj;

    }

    public static function create($request) {
        $firstName = $request['firstName'];
        $lastName = $request['lastName'];
        $emailId = $request['emailId'];
        $facebookId = $request['facebookId'];
        $dateOfBirth = $request['dateOfBirth'];
        $result  = model_user::saveUser($firstName, $lastName, $emailId, $facebookId, $dateOfBirth);
        $resultObj = new stdClass();
        $resultObj->id = $result['id'];
        $resultObj->result = "Success";
        return $resultObj;
    }

}