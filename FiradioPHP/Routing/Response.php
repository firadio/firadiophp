<?php

namespace FiradioPHP\Routing;

use FiradioPHP\F;
use \Exception;

class Response {

    public $fBeginTime = 0; //开始时间
    private $path = ''; //输入用户请求路径
    private $pathinfo = ''; //输入用户请求路径
    private $aParam = array(); // 用于提供给action函数进行处理的参数
    //aRequest的存储优先级，1：HTTP_RAW_POST_DATA为JSON字符串时，2：存在POST时，3：GET请求
    private $aRequest = array(); //输入用户请求数据
    private $mRequestHeader = array(); //$_SERVER['HTTP_AUTHORIZATION']
    private $lResponse = array(); //echo的输出结果
    private $aResponse = array(); //输出的结果
    private $mResponseHeader = array(); //输出的Header
    public $oRequest; //来自于Swoole\Http\Server
    public $oResponse; //来自于Swoole\Http\Server
    public $oServer; //来自于Swoole的onConn
    public $isWebsocket = false;
    public $channels = array();
    public $refFunPar = array();

    public function __construct() {
        $this->initResponse();
    }

    public function __get($name) {
        if ($name === 'path') {
            return $this->path;
        }
        if ($name === 'pathinfo') {
            return $this->pathinfo;
        }
        if ($name === 'aParam') {
            // 提供给Router.php的load_php_file获取参数用的
            return $this->aParam;
        }
        if ($name === 'aRequest') {
            return $this->aRequest;
        }
        if ($name === 'aResponse') {
            // 提供给Router.php的getResponse获取传回数据用的
            return $this->aResponse;
        }
        if ($name === 'mResponseHeader') {
            return $this->mResponseHeader;
        }
        throw new Exception("cannot get property name=$name");
    }

    public function __set($name, $value) {
        if ($name === 'path') {
            $this->pathinfo = pathinfo($value);
            $this->path = F::path_format($value);
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
        if ($name === 'mRequestHeader') {
            $this->mRequestHeader = $value;
            return;
        }
        throw new Exception("dont have property name=$name");
    }

    private function initResponse() {
        $this->fBeginTime = microtime(TRUE);
        $this->aResponse = array();
        $this->aResponse['time'] = $this->fBeginTime;
    }

    public function assign($name, $value) {
        $this->aResponse[$name] = $value;
    }

    public function data($name, $value) {
        if (!isset($this->aResponse['data'])) {
            $this->aResponse['data'] = array();
        }
        $this->aResponse['data'][$name] = $value;
    }

    public function debug($name, $value) {
        if (!isset($this->aResponse['debug'])) {
            $this->aResponse['debug'] = array();
        }
        $this->aResponse['debug'][$name] = $value;
    }

    public function setting($name, $value) {
        if (!isset($this->aResponse['setting'])) {
            $this->aResponse['setting'] = array();
        }
        $this->aResponse['setting'][$name] = (string) $value;
    }

    public function header($name, $value) {
        $this->mResponseHeader[$name] = $value;
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

    public function getRequestHeader($key) {
        if (isset($this->mRequestHeader[$key])) {
            return $this->mRequestHeader[$key];
        }
    }

    public function getExecTime() {
        return microtime(TRUE) - $this->fBeginTime;
    }

    public function getReqMsgContents() {
        if (isset($this->mRequestHeader['worker-msg-content'])) {
            return json_decode(urldecode($this->mRequestHeader['worker-msg-content']), TRUE);
        }
        return array();
    }

    public function appendToResMsg($sMsg) {
        if (!isset($this->mResponseHeader['worker-msg-content'])) {
            $this->mResponseHeader['worker-msg-content'] = array();
        }
        $this->mResponseHeader['worker-msg-content'][] = $sMsg;
    }

    public function putRequest($mParam) {
        foreach ($mParam as $sKey => $sVal) {
            $this->aRequest[$sKey] = $sVal;
        }
    }

    public function getResponseHeaders() {
        $mResHeader = $this->mResponseHeader;
        if (isset($mResHeader['worker-msg-content'])) {
            $mResHeader['worker-msg-content'] = json_encode($mResHeader['worker-msg-content']);
        }
        return $mResHeader;
    }

    public function echo($text) {
        $this->lResponse[] = $text;
    }

    public function getResponseBody() {
        if (empty($this->lResponse)) {
            return json_encode($this->aResponse);
        }
        return implode('', $this->lResponse);
    }

}
