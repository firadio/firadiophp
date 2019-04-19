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

    public static function getDebugArr($traces) {
        $debugArr = array();
        foreach ($traces as $trace) {
            if (!isset($trace['file'])) {
                continue;
            }
            if (!isset($trace['class'])) {
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
            $file = str_replace('\\', '/', $file);
            $debug = array();
            $debug['file'] = $file . '(' . $trace['line'] . ')';
            $debug['func'] = call_user_func(function () use ($trace) {
                $sFunc = '';
                if (isset($trace['class'])) $sFunc .= $trace['class'];
                if (isset($trace['type'])) $sFunc .= $trace['type'];
                $sFunc .= $trace['function'] . '()';
                return $sFunc;
            });
            if (0) {
                $debug['args'] = $trace['args'];
            }
            $debugArr[] = $debug;
        }
        return $debugArr;
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
        $aMessage[] = self::getDebugArr($traces);
        $this->write('error', $aMessage);
    }

    public function write($sLevel, $aMessage) {
         // 默认不写入文件(FALSE)
        $writeable = isset($this->config['writeable']) ? $this->config['writeable'] : FALSE;
        if (!$writeable) {
            return;
        }
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
