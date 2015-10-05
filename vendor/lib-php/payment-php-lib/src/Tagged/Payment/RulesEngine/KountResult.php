<?php

namespace Tagged\Payment\RulesEngine;

/**
 * Implementation of Tagged\Payment\RulesEngine\Result for Kount.
 *
 * @copyright 2012-2014 Tagged, Inc. <http://www.tagged.com/>, All Rights Reserved
 */
class KountResult
{
    const AUTO_RESPONSE_DECLINE = 'D';
    const AUTO_RESPONSE_REVIEW = 'R';
    const AUTO_RESPONSE_MANAGER_REVIEW = 'E';
    const AUTO_RESPONSE_APPROVE = 'A';

    const REASON_CODE_BLACKLIST = "BLACKLIST";
    const REASON_CODE_ADMIN_HOLD = "ADMIN_HOLD";
    const REASON_CODE_2FA_HOLD = "2FA_HOLD";
    const REASON_CODE_DECLINE = "DECLINE";
    const REASON_CODE_2FA_CHECK = "2FA_CHECK";

    const ERROR_KOUNT_RESPONSE = 'kount_response';
    const ERROR_KOUNT_UNKNOWN_REASON = 'kount_unknown_reason';

    private $_userId;
    private $_orderId;
    private $_kountResponse;

    private $_logger;

    /**
     * @param long $userId user id.
     * @param string $orderId order id.
     * @param Kount_Ris_Response $kountResponse response from kount
     */
    public function __construct($userId, $orderId, \Kount_Ris_Response $kountResponse)
    {
        $this->_userId = $userId;
        $this->_orderId = $orderId;

        $this->_kountResponse = $kountResponse;

        $this->_logger = \Tagged\Payment\Logger\Factory::get_instance();
    }

    public function getKountResponse()
    {
        return $this->_kountResponse;
    }

    public function getDecision()
    {
        $autoResponseCode = $this->_kountResponse->getAuto();
        if ($this->_kountResponse->hasErrors()) {
            $this->_logger->logError(
                __METHOD__,
                self::ERROR_KOUNT_RESPONSE,
                join(",", $this->_kountResponse->getErrors()),
                $this->_userId,
                $this->_orderId
            );

            // Chris wants us to proceed on Kount failure
            $result = \Tagged\Payment\Decision::DECISION_APPROVE;
        } else if ($autoResponseCode == self::AUTO_RESPONSE_APPROVE
                   || $autoResponseCode == self::AUTO_RESPONSE_REVIEW
                   || $autoResponseCode == self::AUTO_RESPONSE_MANAGER_REVIEW) {
            $result = \Tagged\Payment\Decision::DECISION_APPROVE;
        } else {
            $reasonCode = $this->_kountResponse->getReasonCode();
            switch ($reasonCode) {
            case self::REASON_CODE_BLACKLIST:
                $result = \Tagged\Payment\Decision::DECISION_BLACKLIST;
                break;
            case self::REASON_CODE_ADMIN_HOLD:
                $result = \Tagged\Payment\Decision::DECISION_GOLDHOLD;
                break;
            case self::REASON_CODE_2FA_HOLD:
                $result = \Tagged\Payment\Decision::DECISION_GOLDHOLD;
                break;
            case self::REASON_CODE_2FA_CHECK:
                $result = $this->_get2FaCheckDecision();
                break;
            case self::REASON_CODE_DECLINE:
                $result = \Tagged\Payment\Decision::DECISION_DECLINE;
                break;
            default:
                $this->_logger->logError(
                    __METHOD__,
                    self::ERROR_KOUNT_UNKNOWN_REASON,
                    "unexpected rule response auto: $autoResponseCode reason: $reasonCode",
                    $this->_userId,
                    $this->_orderId
                );

                // Chris wants us to proceed on Kount failure
                $result = \Tagged\Payment\Decision::DECISION_APPROVE;
                break;
            }
        }

        return $result;
    }

    protected function _is2FaEnabled()
    {
        return \Tagged\Payment\Decision::DECISION_2FA_CHECK;
    }
}
