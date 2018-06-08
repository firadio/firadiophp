<?php

namespace FiradioPHP\System;

class Error {

    public function __construct() {
        register_shutdown_function(array($this, 'shutdown_function'));
    }

    private function is_cli() {
        return preg_match("/cli/i", php_sapi_name()) ? true : false;
    }

    public function shutdown_function() {
        $e = error_get_last();
        if ($this->is_cli()) {
            print_r($e);
        }
    }

}
