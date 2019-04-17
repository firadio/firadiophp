<?php

namespace FiradioPHP\Routing;

use FiradioPHP\F;
use \Exception;

/**
 * 控制器action
 *
 * @author asheng
 */
class Router {

    private $config = array();
    private $cache_funarr = array();
    private $user_cache = array();

    public function __construct($config) {
        $this->config = $config;
    }

    public function __get($name) {
        $this->error("cant get property-name=$name", 'Error In Router');
    }

    public function __set($name, $value) {
        $this->error("cant set property-name=$name", 'Error In Router');
    }

    public function __call($name, $arguments) {
        $modelpre = 'model_';
        if (strpos($name, $modelpre) !== 0) {
            $this->error("cant call fun-name=$name", 'Error In Router');
        }
        $name_ltrim = substr($name, strlen($modelpre));
        $path = $this->config['model_dir'] . DS . str_replace('_', DS, $name_ltrim) . '.php';
        if (!is_file($path)) {
            $this->error("cant find \$path=$path", 'Error In Router');
        }
        $fun = require($path);
        $ret = NULL;
        if (1) {
            $fp = $this->getFucntionParameterForModel($fun, $arguments);
            $ret = call_user_func_array($fun, $fp);
        } else {
            $ret = call_user_func_array($fun, $arguments);
        }
        return $ret;
    }

    public function time() {
        return time();
    }

    /**
     * 获取处理结果
     * @return type
     */
    public function getResponse($oRes) {
        try {
            //$this->beginTransactionAll();
            $this->execAction($oRes);
            //$this->rollbackAll();
        } catch (Exception $ex) {
            //$this->rollbackAll();
            $exCode = $ex->getCode();
            // code >= 0 无异常
            // code = -1 用于输出特殊格式资料
            // code = -2 自定义错误
            // code = -3 其他未知错误
            if ($exCode === 0) $exCode = -3;
            $oRes->assign('code', $exCode);
            if ($exCode === -1) {
                //该异常由$oRes->end();发起
                throw new Exception($ex->getMessage(), $exCode);
            }
            $traces = $ex->getTrace();
            $message = $ex->getMessage();
            $message = str_replace(APP_ROOT, '', $message);
            $oRes->assign('message', $message);
            if (!empty($ex->title)) {
                $oRes->assign('title', $ex->title);
            }
            if ($exCode === -2) {
                //自定义异常无需debug调试
                //该异常由$this->error($message)发起
                $oRes->assign('errno', 2);
                return $oRes->response;
            }
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
            $oRes->assign('debug', $debugArr);
        }
        return $oRes->response;
    }

    private function end($html) {
        throw new Exception($html, -1);
    }

    private function html($path, $aData) {
        $html = file_get_contents(__DIR__ . DS . $path);
        foreach ($aData as $key => $value) {
            $html = str_replace('{$' . $key . '}', $value, $html);
        }
        throw new Exception($html, -1);
    }

    private function getFuncInfo($file_path) {
        if (!$this->config['cache_enable']) {
            return $this->getFuncInfoByFile($file_path);
        }
        if (array_key_exists($file_path, $this->cache_funarr)) {
            F::debug('getFuncInfoInCache');
            return $this->cache_funarr[$file_path];
        }
        return $this->cache_funarr[$file_path] = $this->getFuncInfoByFile($file_path);
    }

    private function getFuncInfoByFile($file_path) {
        $funcInfo = array();
        $funcInfo['func'] = $this->file_require($file_path);
        $ReflectionFunc = new \ReflectionFunction($funcInfo['func']);
        $funcInfo['refFunPar'] = $ReflectionFunc->getParameters();
        return $funcInfo;
    }

    private function file_require($file_path) {
        if (!is_file($file_path)) {
            $file_path = substr($file_path, strlen($this->config['action_dir']));
            $this->error($file_path . ' Not Found Action', 'Error In Router');
        }
        $func = require($file_path);
        if (gettype($func) !== 'object') {
            $this->error('return not a object(function) in file=' . $file_path, 'Error In Router');
        }
        return $func;
    }

    private function load_php_file($file, $oRes) {
        if (($funcInfo = $this->getFuncInfo($file)) === false) {
            return false;
        }
        $argv = $oRes->request;
        $fp = $this->getFucntionParameterForAction($funcInfo['refFunPar'], $argv, $oRes);
        try {
            call_user_func_array($funcInfo['func'], $fp[0]);
            foreach ($fp[1] as $sName => $oInstance) {
                F::$oConfig->freeInstance($sName, $oInstance);
            }
        } catch (Exception $ex) {
            foreach ($fp[1] as $sName => $oInstance) {
                F::$oConfig->freeInstance($sName, $oInstance);
            }
            throw $ex;
        }
        return true;
    }

    /**
     * 来源https://zhidao.baidu.com/question/691583631801759684.html
     * 获取一个函数的依赖
     * @param  string|callable $func
     * @param  array  $param 调用方法时所需参数 形参名就是key值
     * @return array  返回方法调用所需依赖
     */
    private function getFucntionParameterForModel($func, $param = []) {
        $ReflectionFunc = new \ReflectionFunction($func);
        $depend = array();
        foreach ($ReflectionFunc->getParameters() as $key => $value) {
            if (isset($param[$key])) {
                $depend[] = $param[$key];
            } elseif ($value->isDefaultValueAvailable()) {
                $depend[] = $value->getDefaultValue();
            } else {
                $depend[] = NULL;
            }
        }
        return $depend;
    }

    private function getFucntionParameterForAction($refFunPar, $param, $oRes) {
        if (!is_array($param)) {
            $param = [$param];
        }
        $depend = array();
        $getInstance = array();
        foreach ($refFunPar as $value) {
            $matches = array();
            //这3个都是用户主动传入的，分别是aRequest,aArgv,sRawContent
            if ($value->name === 'request') {
                $depend[] = $oRes->request;
                continue;
            }
            if ($value->name === 'aArgv') {
                $depend[] = $oRes->aArgv;
                continue;
            }
            if ($value->name === 'sRawContent') {
                $depend[] = $oRes->sRawContent;
                continue;
            }
            //这3个字符串都是用户被动传入的，分别是IPADDR,JSCOOKID,APICOOKID
            if ($value->name === 'IPADDR') {
                $depend[] = $oRes->ipaddr;
                continue;
            }
            if ($value->name === 'JSCOOKID') {
                $depend[] = md5($oRes->sessionId);
                continue;
            }
            if ($value->name === 'APICOOKID') {
                $depend[] = md5($oRes->APICOOKID);
                continue;
            }
            //由Response实例化的object
            if ($value->name === 'oRes') {
                $depend[] = $oRes;
                continue;
            }
            //开始处理由config实例化的object
            if (preg_match('/^o([A-Z][a-z0-9_]+)$/', $value->name, $matches)) {
                $sName = strtolower($matches[1]);
                $oInstance = F::$oConfig->getInstance($sName);
                $getInstance[$sName] = $oInstance;
                $depend[] = $oInstance;
                //$depend[] = F::$aInstances[$sName];
                continue;
            }
            if (isset(F::$oConfig->aClass[$value->name])) {
                $sName = $value->name;
                $oInstance = F::$oConfig->getInstance($sName);
                $getInstance[$sName] = $oInstance;
                $depend[] = $oInstance;
                continue;
            }
            // 所有的特殊参数都应该在用户参数处理之前
            if (isset($param[$value->name])) {
                $depend[] = $param[$value->name];
                continue;
            }
            // 最后才处理默认值
            if ($value->isDefaultValueAvailable()) {
                $depend[] = $value->getDefaultValue();
                continue;
            }
            $depend[] = null;
        }
        return array($depend, $getInstance);
    }

    /**
     * 执行action行为器
     * @return boolean
     */
    private function execAction($oRes) {
        if ($oRes->path === '' || $oRes->path === '/') {
            return $this->load_php_file($this->config['action_dir'] . '/index.php', $oRes);
        }
        $aPath = explode('/', $oRes->path);
        $sPath = '';
        foreach ($aPath as $dir) {
            if ($dir === '') {
                continue;
            }
            $sPath .= DS . $dir;
            $ret = $this->load_php_file($this->config['action_dir'] . $sPath . '.php', $oRes);
            if ($ret === false) {
                return false;
            }
        }
        return true;
    }

    private function error($message, $param2 = '提示') {
        $exCode = -2;
        if (is_numeric($param2)) {
            $exCode = $param2;
        }
        $ex = new Exception($message, $exCode);
        if (is_string($param2)) {
            $ex->title = $param2;
        }
        throw $ex;
    }

    private function model_error($message, $title = '提示') {
        $ex = new ModelException($message, -2);
        $ex->title = $title;
        throw $ex;
    }

    private function getCache($table, $key) {
        if (!isset($this->user_cache[$table])) {
            return false;
        }
        if (!isset($this->user_cache[$table][$key])) {
            return false;
        }
        return $this->user_cache[$table][$key];
    }

    private function setCache($table, $key, $value) {
        if (!isset($this->user_cache[$table])) {
            $this->user_cache[$table] = array();
        }
        $this->user_cache[$table][$key] = $value;
    }

    private function logWrite($sLevel, $sMessage) {
        F::logWrite($sLevel, $sMessage);
    }

}
