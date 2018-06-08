<?php

/*
 * 系统日志
 */

namespace FiradioPHP\System;

use FiradioPHP\F;

/**
 * Description of Log
 *
 * @author asheng
 */
class Log {

    private $config;

    public function __construct($config) {
        $this->config = $config;
    }

    public function debug($aMessage) {
        $this->write('debug', $aMessage);
    }

    public function info($aMessage) {
        $this->write('info', $aMessage);
    }

    public function error($aMessage, $ex = NULL) {
        if (is_string($aMessage)) {
            $aMessage = array($aMessage);
        }
        $traces = array();
        if ($ex !== NULL && method_exists($ex, 'getTrace')) {
            $traces = $ex->getTrace();
        } else {
            $traces = debug_backtrace();
        }
        $debugArr = array();
        foreach ($traces as $trace) {
            if (!isset($trace['file'])) {
                continue;
            }
            if (0 !== strpos($trace['file'], APP_ROOT)) {
                continue;
            }
            if ($trace['class'] === 'FiradioPHP\\F' && $trace['function'] === 'start') {
                continue;
            }
            if ($trace['class'] === 'Workerman\\Worker' && $trace['function'] === 'runAll') {
                continue;
            }
            $file = substr($trace['file'], strlen(APP_ROOT));
            $debug = array();
            $debug['msg'] = '' . $file . '(' . $trace['line'] . '):';
            $debug['msg'] .= '' . $trace['class'] . $trace['type'] . $trace['function'] . '()';
            if (0) {
                $debug['args'] = $trace['args'];
            }
            $debugArr[] = $debug;
        }
        $aMessage[] = $debugArr;
        $this->write('error', $aMessage);
    }

    public function write($sLevel, $aMessage) {
        if (!is_array($aMessage)) {
            $aMessage = array('message' => $aMessage);
        }
        $aData = array();
        $aData['time'] = microtime(TRUE);
        foreach ($aMessage as $sKey => $sMessage) {
            $aData[$sKey] = F::json_decode($sMessage);
        }
        $sData = json_encode($aData) . "\r\n";
        $this->mkDirs($this->config['pre_dir']);
        $filename = $this->config['pre_dir'] . DS . $sLevel . '.log';
        if (is_file($filename)) {
            return file_put_contents($filename, $sData, FILE_APPEND);
        }
        return file_put_contents($filename, $sData);
    }

    private function mkDirs($path) {
        if (is_dir($path)) {
            //已经是目录了就不用创建
            return true;
        }
        if (is_dir(dirname($path))) {
            //父目录已经存在，直接创建
            return mkdir($path);
        }
        //从子目录往上创建
        $this->mkDirs(dirname($path));
        //因为有父目录，所以可以创建路径
        return mkdir($path);
    }

}
