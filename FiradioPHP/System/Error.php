<?php

namespace FiradioPHP\System;

class Error {

    public function __construct() {
        register_shutdown_function(array($this, 'shutdown_function'));
        // set_error_handler(array($this, 'handleError'), E_ALL | E_STRICT);
    }

    private function is_cli() {
        return preg_match("/cli/i", php_sapi_name()) ? true : false;
    }

    public function shutdown_function() {
        // 对于PHP Fatal error:  Uncaught Error: Call to undefined method
        // 只能通过shutdown_function拦截，仅适用于index.php方式
        $e = error_get_last();
        if (empty($e)) {
            return;
        }
        if ($this->is_cli()) {
            print_r($e);
            return;
        }
        $e['time'] = microtime(true);
        exit(json_encode($e));
    }

/*
    public function handleError($code, $description, $file = null, $line = null, $context = null) {
        list($error, $log) = $this->mapErrorCode($code);
        $data = array(
            'level' => $log,
            'code' => $code,
            'error' => $error,
            'description' => $description,
            'file' => $file,
            'line' => $line,
            'context' => $context,
            'path' => $file,
            'message' => $error . ' (' . $code . '): ' . $description . ' in [' . $file . ', line ' . $line . ']'
        );
        print_r($data);
        //return fileLog($data);
    }

    private function mapErrorCode($code) {
        $error = $log = null;
        switch ($code) {
            case E_PARSE:
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                $error = 'Fatal Error';
                $log = LOG_ERR;
                break;
            case E_WARNING:
            case E_USER_WARNING:
            case E_COMPILE_WARNING:
            case E_RECOVERABLE_ERROR:
                $error = 'Warning';
                $log = LOG_WARNING;
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                $error = 'Notice';
                $log = LOG_NOTICE;
                break;
            case E_STRICT:
                $error = 'Strict';
                $log = LOG_NOTICE;
                break;
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $error = 'Deprecated';
                $log = LOG_NOTICE;
                break;
            default :
                break;
        }
        return array($error, $log);
    }
//*/

}
