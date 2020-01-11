<?php

namespace FiradioPHP\Routing;

use FiradioPHP\F;
use \Exception;

class Response {

    public $fBeginTime = 0; //开始时间
    private $ipaddr = ''; //输入网络IP地址
    private $path = ''; //输入用户请求路径
    private $pathinfo = ''; //输入用户请求路径
    private $sessionId = ''; //会话ID
    private $aSession = array(); //会话
    private $aParam = array(); // 用于提供给action函数进行处理的参数
    //aRequest的存储优先级，1：HTTP_RAW_POST_DATA为JSON字符串时，2：存在POST时，3：GET请求
    private $aRequest = array(); //输入用户请求数据
    private $aResponse = array(); //输出的结果
    private $aResponseHeader = array(); //输出的Header
    public $oRequest; //来自于Swoole\Http\Server
    public $oResponse; //来自于Swoole\Http\Server
    public $oServer; //来自于Swoole的onConn
    public $isWebsocket = false;
    public $aHeader = array(); //$_SERVER['HTTP_AUTHORIZATION']
    public $sRawContent; //保存原始的HTTP_RAW_POST_DATA
    public $channels = array();
    public $refFunPar = array();

    public function __construct() {
        $this->initResponse();
    }

    public function __get($name) {
        if ($name === 'ipaddr') {
            return $this->ipaddr;
        }
        if ($name === 'path') {
            return $this->path;
        }
        if ($name === 'pathinfo') {
            return $this->pathinfo;
        }
        if ($name === 'sessionId') {
            return $this->sessionId;
        }
        if ($name === 'aParam') {
            // 提供给Router.php的load_php_file获取参数用的
            return $this->aParam;
        }
        if ($name === 'aRequest') {
            return $this->aRequest;
        }
        if ($name === 'aResponse' || $name === 'response') {
            // 提供给Router.php的getResponse获取传回数据用的
            return $this->aResponse;
        }
        if ($name === 'aResponseHeader') {
            return $this->aResponseHeader;
        }
        if ($name === 'aSession') {
            return $this->aSession;
        }
        throw new Exception("cannot get property name=$name");
    }

    public function __set($name, $value) {
        if ($name === 'ipaddr') {
            $this->ipaddr = $value;
            $this->aResponse['ipaddr'] = $this->ipaddr;
            return;
        }
        if ($name === 'path') {
            $this->pathinfo = pathinfo($value);
            $this->path = F::path_format($value);
            return;
        }
        if ($name === 'sessionId') {
            $this->sessionId = $value;
            return;
        }
        if ($name === 'APICOOKID') {
            $this->APICOOKID = $value;
            return;
        }
        if ($name === 'aParam') {
            throw new Exception('not allow to set aParam, only use setParam');
            $this->aParam = $value;
            return;
        }
        if ($name === 'aRequest') {
            $this->aRequest = $value;
            return;
        }
        if ($name === 'aSession') {
            $this->aSession = $value;
            return;
        }
        throw new Exception("dont have property name=$name");
    }

    private function initResponse() {
        $this->aResponse = array();
        $this->aResponse['time'] = microtime(TRUE);
    }

    public function assign($name, $value) {
        $this->aResponse[$name] = $value;
    }

    public function header($name, $value) {
        $this->aResponseHeader[$name] = $value;
    }

    public function response($name) {
        return $this->aResponse[$name];
    }

    /**
     * 合并原来的response，但可能用不到，暂时保留
     * @param type $newResult
     */
    public function mergeResponse($newResult) {
        $this->aResponse = array_merge($this->aResponse, $newResult);
    }

    public function message($message, $title = '提示') {
        $this->aResponse['message'] = $message;
        $this->aResponse['title'] = $title;
    }

    public function setParam($name, $value) {
        // 在父action里setParam后，子action即可取得aParam
        $this->aParam[$name] = $value;
    }

    public function end($str) {
        throw new \Exception($str, -1);
    }

    public function setcookie($name, $value = "", $expire = 0, $path = '', $domain = '') {
        if (method_exists($this->oResponse, 'cookie')) {
            $this->oResponse->cookie($name, $value, $expire, $path, $domain);
            return;
        }
        F::setcookie($name, $value, $expire, $path, $domain);
    }

    public function getHeader($key) {
        if (isset($this->aHeader[$key])) {
            return $this->aHeader[$key];
        }
        $key = strtolower($key);
        if (isset($this->aHeader[$key])) {
            return $this->aHeader[$key];
        }
        $key = strtoupper($key);
        if (isset($this->aHeader[$key])) {
            return $this->aHeader[$key];
        }
    }

}
