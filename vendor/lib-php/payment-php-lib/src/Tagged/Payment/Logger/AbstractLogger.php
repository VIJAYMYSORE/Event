<?php

namespace Tagged\Payment\Logger;

abstract class AbstractLogger
{
    protected abstract function _log($method, $errorMsg, $extra = '', $userId = null, $orderId = null, $severity = 8);

    public function logError($method, $errorMsg, $extra = '', $userId = null, $orderId = null, $severity = 8)
    {
        return $this->_log($method, $errorMsg, $extra, $userId, $orderId, $severity);
    }

    public function logErrorException($method, $error_code, $e, $userId = null, $orderId = null, $severity = 8)
    {
        $extra = $e->getMessage() . "\n" . $e->getTraceAsString();
        return $this->_log($method, $errorMsg, $extra, $userId, $orderId, $severity);
    }
}
