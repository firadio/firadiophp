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
        $ext = strtolower($oRes->pathinfo['extension']);
        try {
            if ($ext === '' || $ext === 'php' || $ext === 'api') {
                //$this->beginTransactionAll();
                $this->execAction($oRes);
                //$this->rollbackAll();
            } else if ($ext === 'htm' || $ext === 'html') {
                $this->execAction($oRes);
            } else {
                $this->error('请求的文件扩展名错误', 'Error In FileExtension');
            }
        } catch (Exception $ex) {
            //$this->rollbackAll();
            $exCode = $ex->getCode();
            $oRes->assign('code', $exCode);
            if ($exCode === -1) {
                //该异常由$oRes->end();发起
                //throw new Exception($ex->getMessage(), $exCode);
                $this->html($ex->getMessage());
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
            $oRes->assign('debug', $debugArr);
        }
        return $oRes->response;
    }

    private function html($html) {
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
        $ext = strtolower($oRes->pathinfo['extension']);
        if ($ext === 'html' || $ext === 'htm') {
            //把参数列表传给params
            foreach ($funcInfo['refFunPar'] as $obj) {
                $oRes->refFunPar[$obj->name] = array();
            }
            return true;
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
            if (isset($param[$value->name])) {
                $depend[] = $param[$value->name];
                continue;
            }
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
        $ext = strtolower($oRes->pathinfo['extension']);
        if ($ext === 'html' || $ext === 'htm') {
            $html = file_get_contents(__DIR__ . DS . 'api.html');
            $this->html('test1');
        }
        return true;
    }

    private function error($message, $title = '提示') {
        $ex = new Exception($message, -2);
        $ex->title = $title;
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
