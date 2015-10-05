<?php

namespace Tagged\Payment\Logger;

class Factory
{
    // XXX we need a PCI implementation
    public static function get_instance()
    {
        if (class_exists('tag_syslog')) {
            return new TaggedLoggerAdaptor();
        }
    }
}
