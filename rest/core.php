<?php
class rest_core extends \Tagged\Rest\Router {
    /**
     * Returns the name of the class for the controller
     */
    public function getControllerClass($controller) {
        return "$this->baseNamespace"."_$controller";
    }
}