<?php

namespace FiradioPHP\Routing;

use FiradioPHP\F;
use \Exception;

class Response {

    public $fBeginTime = 0; //开始时间
    private $ipaddr = ''; //输入网络IP地址
    private $path = ''; //输入用户请求路径
    private $sessionId = ''; //会话ID
    private $APICOOKID = ''; //会话ID
    private $request = ''; //输入用户请求数据
    private $response = array(); //输出的结果
    private $aArgv = array(); //来自命令行
    public $oRequest; //来自于Swoole\Http\Server
    public $oResponse; //来自于Swoole\Http\Server
    public $oServer; //来自于Swoole的onConn
    public $isWebsocket = false;
    public $aHeader = array(); //$_SERVER['HTTP_AUTHORIZATION']
    //aRequest的存储优先级，1：HTTP_RAW_POST_DATA为JSON字符串时，2：存在POST时，3：GET请求
    public $aRequest = array();
    public $aParam = array();
    public $sRawContent; //保存原始的HTTP_RAW_POST_DATA
    public $channels = array();

    public function __get($name) {
        if ($name === 'ipaddr') {
            return $this->ipaddr;
        }
        if ($name === 'path') {
            return $this->path;
        }
        if ($name === 'sessionId') {
            return $this->sessionId;
        }
        if ($name === 'APICOOKID') {
            return $this->APICOOKID;
        }
        if ($name === 'request') {
            return $this->request;
        }
        if ($name === 'response') {
            return $this->response;
        }
        if ($name === 'aArgv') {
            return $this->aArgv;
        }
        throw new Exception("cannot get property name=$name");
    }

    public function __set($name, $value) {
        if ($name === 'ipaddr') {
            $this->ipaddr = $value;
            return;
        }
        if ($name === 'path') {
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
        if ($name === 'request') {
            $this->request = $value;
            $this->initResponse();
            return;
        }
        if ($name === 'aArgv') {
            $this->aArgv = $value;
            $this->initResponse();
            return;
        }
        throw new Exception("dont have property name=$name");
    }

    public function initResponse() {
        $this->response = array();
        $this->response['time'] = microtime(TRUE);
    }

    public function assign($name, $value) {
        $this->response[$name] = $value;
    }

    public function response($name) {
        return $this->response[$name];
    }

    /**
     * 合并原来的response，但可能用不到，暂时保留
     * @param type $newResult
     */
    public function mergeResponse($newResult) {
        $this->response = array_merge($this->response, $newResult);
    }

    public function message($message, $title = '提示') {
        $this->response['message'] = $message;
        $this->response['title'] = $title;
    }

    public function setParam($name, $value) {
        $this->request[$name] = $value;
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
