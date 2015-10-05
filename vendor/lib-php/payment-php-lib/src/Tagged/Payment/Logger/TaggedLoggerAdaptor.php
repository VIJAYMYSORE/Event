<?php

namespace Tagged\Payment\Logger;

/**
 * XXX this is a quick hack, we should unify with tag_payment_error
 */
class TaggedLoggerAdaptor extends AbstractLogger
{
    protected function _log($method, $errorCode, $extra = '', $userId = null, $orderId = null, $severity = 8)
    {
        \tag_syslog::warn("payment_error method: $method, error_code: $errorCode, user_id: $userId, order_id: $orderId, extra: $extra, severity: $severity");
        \tag_log::log_message_pageview('payment_error', array($userId, $orderId, $method, $errorCode, $severity, $extra));
    }
}
