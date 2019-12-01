<?php

namespace FiradioPHP;

use \Exception;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('CONFIG_ADMIN_WX')) {
    define('CONFIG_ADMIN_WX', 83550102);
}

class F {

    public static $oError;
    public static $oConfig;
    public static $aInstances = array();
    public static $aClient = array();

    public static function init($configDir = '') {
        date_default_timezone_set('PRC');
        mb_internal_encoding('UTF-8');
        mb_http_output('UTF-8');
        mb_http_input('UTF-8');
        mb_regex_encoding('UTF-8');
        try {
            spl_autoload_register('\FiradioPHP\F::loadByNamespace');
            self::$oError = new System\Error();
            self::$oConfig = new System\Config($configDir);
        } catch (Exception $ex) {
            switch ($ex->getCode()) {
                case(1045):
                    throw new Exception('[1045]数据库登录失败');
                default:
            }
            throw $ex;
        }
    }

    public static function loadByNamespace($name) {
        $dir = str_replace('\\', DS, $name);
        $file = dirname(__DIR__) . DS . $dir . '.php';
        if (is_file($file)) {
            require $file;
        }
    }

    public static function formatPath($sPath) {
        $sPath = str_replace('\\', '/', $sPath);
        $sPath = str_replace('..', '.', $sPath);
        $sPath = str_replace('./', '', $sPath);
        $sPath = str_replace('/.', '/', $sPath);
        $sPath = str_replace('//', '/', $sPath);
        return $sPath;
    }

    public static function path_format($sPath) {
        $path = self::formatPath($sPath);
        $i = strrpos($path, '.');
        if ($i !== false) {
            //去掉扩展名(比如.php)
            $path = substr($path, 0, $i);
        }
        //$path = trim($path, '/');
        return $path;
    }

    public static function header($content) {
        if (class_exists('\Workerman\Protocols\Http')) {
            \Workerman\Protocols\Http::header($content);
            return;
        }
        header($content);
    }

    public static function end($msg) {
        if (class_exists('\Workerman\Protocols\Http')) {
            \Workerman\Protocols\Http::end($msg);
            return;
        }
        exit($msg);
    }

    public static function logWrite($sLevel, $sMessage) {
        if (isset(F::$aInstances['log'])) {
            F::$aInstances['log']->write($sLevel, $sMessage);
        }
    }

    public static function console_log($aMessage) {
        $aMessage['time'] = microtime(TRUE);
        if (function_exists('websocket_send')) {
            websocket_send('console', $aMessage);
        }
    }

    public static function debug($message) {
        if (is_array($message)) {
            $message = print_r($message, true);
        }
        if (0) {
            echo date('Y-m-d H:i:s') . ' ' . $message . "\r\n";
        }
        if (isset(F::$aInstances['log'])) {
            F::$aInstances['log']->debug($message);
        }
    }

    public static function info($aMessage) {
        if (is_string($aMessage)) {
            $aMessage = array('message' => $aMessage);
        }
        self::console_log($aMessage);
        if (isset(F::$aInstances['log'])) {
            F::$aInstances['log']->info($aMessage);
        }
    }

    public static function error($aMessage, $ex = NULL) {
        if (is_string($aMessage)) {
            $aMessage = array('message' => $aMessage);
        }
        // echo json_encode($aMessage) . PHP_EOL;
        self::console_log($aMessage);
        if (isset(F::$aInstances['log'])) {
            F::$aInstances['log']->error($aMessage, $ex);
        }
        throw new Exception($aMessage['message'], -2);
    }

    public static function setcookie($name, $value = "", $expire = 0, $path = '', $domain = '') {
        if (class_exists('\Workerman\Protocols\Http')) {
            \Workerman\Protocols\Http::setcookie($name, $value, $expire, $path, $domain = '');
            return;
        }
        setcookie($name, $value, $expire, $path, $domain);
    }

    public static function headerAllowOrigin($origin) {
        $row = array();
        if (class_exists('\Swoole\Http\Server')) {
            $row['Access-Control-Allow-Origin'] = $origin;
            return $row;
        }
        self::header('Access-Control-Allow-Origin: ' . $origin);
    }

    public static function json_decode($sJson) {
        if (!is_string($sJson)) {
            return $sJson;
        }
        if (substr($sJson, 0, 1) !== '{') {
            return $sJson;
        }
        $aJson = json_decode($sJson, true);
        return $aJson;
    }

    public static function matchPrefix($sOrigin, $sPrefix) {
        return substr($sOrigin, 0, strlen($sPrefix)) === $sPrefix;
    }

    public static function getResponse($oRes, $oCallBack, $oServer = NULL, $fd = NULL) {
        if (empty($oRes->oRequest)) {
            return;
        }
        $info = array();
        $info['ipaddr'] = $oRes->ipaddr;
        $info['path'] = $oRes->path;
        $result = array();
        $iCode = 0;
        try {
            //5：输出处理结果
            $result = self::$aInstances['router']->getResponse($oRes);
            $iCode = isset($result['code']) ? $result['code'] : 0;
        } catch (Exception $ex) {
            $iCode = $ex->getCode();
            if ($iCode !== -1) {
                $info[] = $ex->getMessage();
            }
        }
        //$begin_time = $oRes->oRequest->server['request_time_float'];
        $fBeginTime = $oRes->fBeginTime;
        $result['execute_time'] = microtime(TRUE) - $fBeginTime;
        $info['execute_time'] = $result['execute_time'];
        //$info['execute_time'] = execute_time($fBeginTime) . 'ms';
        self::info($info);
        if ($iCode === -1) {
            //$iCode=-1并不是错误，而是action需要输出自定义格式
            $oCallBack($ex->getMessage(), $oServer, $fd);
            return;
        }
        if (!empty($result['errno'])) {
            print_r($result);
        }
        $oCallBack(json_encode($result), $oServer, $fd);
        if ($iCode === 0) {
            if ($oRes->path === '/command/die') {
                $oServer->shutdown();
            }
        }
    }

    public static function scanDirTree($sBaseDir, $sSubDir, $callBack, $aParam = NULL) {
        $dir = scandir($sBaseDir . DS . $sSubDir);
        foreach ($dir as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $sBaseDir . DS . $sSubDir . DS . $file;
            if (is_file($path)) {
                if (is_callable($callBack)) {
                    $callBack(array($sBaseDir, $sSubDir, $file), $aParam);
                }
                continue;
            }
            $sNewSubDir = ((strlen($sSubDir) === 0) ? '' : $sSubDir . DS) . $file;
            self::scanDirTree($sBaseDir, $sNewSubDir, $callBack, $aParam);
        }
    }

}

if (function_exists('opcache_compile_file')) {
    \FiradioPHP\F::scanDirTree(__DIR__, '', function($a) {
        if ($a[1] === '') return;
        $path = implode(DS, $a);
        $pathinfo = pathinfo($path);
        if ($pathinfo['extension'] !== 'php') return;
        opcache_compile_file($path);
    });
}


