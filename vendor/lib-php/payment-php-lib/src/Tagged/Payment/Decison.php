<?php

namespace Tagged\Payment;

/**
 * XXX merge with tag_payment_proxy_decision
 */
class Decision
{
    const DECISION_NULL = 21;
    const DECISION_APPROVE = 22;
    const DECISION_DECLINE = 23;

    const DECISION_GOLDHOLD = 24;
    const DECISION_GOLDLOCK = 25;
    const DECISION_GOLDHOLD_2FA = 243;

    const DECISION_BLACKLIST = 26;

    const DECISION_2FA_CHECK = 29;
}
