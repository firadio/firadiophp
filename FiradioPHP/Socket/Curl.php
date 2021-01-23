<?php

namespace FiradioPHP\Socket;

use \Exception;

class Curl {

    protected $_useragent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1';
    protected $_urlpre = '';
    protected $_followlocation = NULL;
    protected $_timeout = 10;
    protected $_maxRedirects = NULL;
    protected $_cookieFileLocation = NULL;
    protected $_post = FALSE;
    protected $_postFields = array();
    protected $_referer = NULL;
    protected $_session;
    protected $_webpage;
    protected $_includeHeader;
    protected $_noBody;
    protected $_status;
    private $_upload = FALSE;
    private $_header = array();
    public $response_header = array();
    public $authentication = 0;
    public $auth_name = '';
    public $auth_pass = '';
    public $postFormat = '';
    private $_params = array();
    private $_method = NULL;
    private $_socks5 = NULL;

    public function useAuth($use) {
        $this->authentication = 0;
        if ($use == true) {
            $this->authentication = 1;
        }
    }

    public function setName($name) {
        $this->auth_name = $name;
    }

    public function setPass($pass) {
        $this->auth_pass = $pass;
    }

    public function __construct($urlpre = NULL) {
        if ($urlpre !== NULL) {
            $this->_urlpre = $urlpre;
        }
    }

    public function setReferer($referer) {
        $this->_referer = $referer;
    }

    public function setUrlPre($urlpre) {
        $this->_urlpre = $urlpre;
        $this->_fullUrl = $this->urlAddParams($this->_urlpre, $this->_params);
    }

    public function setCookiFileLocation($path) {
        $this->_cookieFileLocation = $path;
    }

    public function setParam($params) {
        $this->_params = $params;
        $this->_fullUrl = $this->urlAddParams($this->_urlpre, $this->_params);
    }

    public function setPost($postFields) {
        $this->_post = true;
        $this->_postFields = $postFields;
    }

    public function setUserAgent($userAgent) {
        $this->_useragent = $userAgent;
    }

    private function urlAddParams($urlpre, $params) {
        if ($params === '' || $params === NULL) {
            return $urlpre;
        }
        if (is_string($params)) {
            if (substr($params, 0, 1) === '/') {
                return $urlpre . $params;
            } else if (substr($params, 0, 1) === '?') {
                return $urlpre . $params;
            }
        }
        if (is_array($params)) {
            $params = http_build_query($params);
        }
        $url = $urlpre;
        if ($params) {
            if (strpos($urlpre, '?') > 0) {
                $url .= '&' . $params;
            } else {
                $url .= '?' . $params;
            }
        }
        return $url;
    }

    public function createCurl($path = '') {
        if ($this->_post) {
            if ($this->_upload) {
                $this->setHeader('Content-Type', 'multipart/form-data');
            } else if (empty($this->getHeader('Content-Type'))) {
                $this->setHeader('Content-Type', 'application/x-www-form-urlencoded');
            }
        }
        $this->_fullUrl = $this->urlAddParams($this->_urlpre, $path);
        return $this->execCurl();
    }

    public function execCurl() {
        $s = curl_init();
        curl_setopt($s, CURLOPT_URL, $this->_fullUrl);
        curl_setopt($s, CURLOPT_HTTPHEADER, $this->CURLOPT_HTTPHEADER());
        if ($this->_timeout !== NULL) {
            curl_setopt($s, CURLOPT_TIMEOUT, $this->_timeout);
        }
        if ($this->_method !== NULL) {
            curl_setopt($s, CURLOPT_CUSTOMREQUEST, $this->_method);
        }
        if ($this->_socks5) {
            curl_setopt($s, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);  
            curl_setopt($s, CURLOPT_PROXY, $this->_socks5);  
            //curl_setopt($s,CURLOPT_PROXYUSERPWD, "username:pwd");  
        }
        if ($this->_maxRedirects !== NULL) {
            curl_setopt($s, CURLOPT_MAXREDIRS, $this->_maxRedirects);
        }
        curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
        if ($this->_followlocation !== NULL) {
            curl_setopt($s, CURLOPT_FOLLOWLOCATION, $this->_followlocation);
        }
        if (!isset($this->_header['Cookie']) && $this->_cookieFileLocation !== NULL) {
            curl_setopt($s, CURLOPT_COOKIEJAR, $this->_cookieFileLocation);
            curl_setopt($s, CURLOPT_COOKIEFILE, $this->_cookieFileLocation);
        }

        if (0) {
            curl_setopt($s, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //代理认证模式
            curl_setopt($s, CURLOPT_PROXY, '10.86.2.9'); //代理服务器地址
            curl_setopt($s, CURLOPT_PROXYPORT, 8888); //代理服务器端口
        }

        curl_setopt($s, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($s, CURLOPT_SSL_VERIFYHOST, FALSE);

        if ($this->authentication == 1) {
            curl_setopt($s, CURLOPT_USERPWD, $this->auth_name . ':' . $this->auth_pass);
        }
        if ($this->_post) {
            $post = $this->_postFields;
            if ($this->_upload) {
                
            } else
            if (is_array($post)) {
                $sContentType = isset($this->_header['Content-Type']) ? $this->_header['Content-Type'] : '';
                $postFormat = isset($this->postFormat) ? strtolower($this->postFormat) : '';
                if ($postFormat === 'json') {
                    $post = json_encode($post);
                } else if (strpos($sContentType, 'application/json') !== FALSE) {
                    $post = json_encode($post);
                } else {
                    $post = http_build_query($post);
                }
            }
            curl_setopt($s, CURLOPT_POST, true);
            curl_setopt($s, CURLOPT_POSTFIELDS, $post);
        }

        if ($this->_includeHeader) {
            curl_setopt($s, CURLOPT_HEADER, true);
        }

        if ($this->_noBody) {
            curl_setopt($s, CURLOPT_NOBODY, true);
        }
        /*
          if($this->_binary)
          {
          curl_setopt($s,CURLOPT_BINARYTRANSFER,true);
          }
         */
        if ($this->_useragent) {
            curl_setopt($s, CURLOPT_USERAGENT, $this->_useragent);
        }
        if ($this->_referer !== NULL) {
            curl_setopt($s, CURLOPT_REFERER, $this->_referer);
        }
        if ($this->_upload) {
            if (class_exists('\CURLFile')) {
                curl_setopt($s, CURLOPT_SAFE_UPLOAD, TRUE);
            } else if (defined('CURLOPT_SAFE_UPLOAD')) {
                curl_setopt($s, CURLOPT_SAFE_UPLOAD, FALSE);
            }
        }

        if (!empty($this->sslcert_file)) {
            curl_setopt($s, CURLOPT_SSLCERTTYPE, 'PEM'); //sslCertType
            curl_setopt($s, CURLOPT_SSLCERT, $this->sslcert_file);
        }

        if (!empty($this->sslkey_file)) {
            curl_setopt($s, CURLOPT_SSLKEYTYPE, 'PEM'); //sslKeyType
            curl_setopt($s, CURLOPT_SSLKEY, $this->sslkey_file);
        }
        $exec_result = curl_exec($s);
        if ($exec_result === FALSE) {
            throw new Exception('exec_result is FALSE, Error: ' . curl_error($s) . $this->_fullUrl);
        }
        if ($this->_includeHeader) {
            $this->getHeaderBody($exec_result, $this->response_header, $this->_webpage);
        } else {
            $this->_webpage = $exec_result;
        }

        $this->_status = curl_getinfo($s, CURLINFO_HTTP_CODE);
        $error = curl_error($s);
        curl_close($s);
        if ($error) {
            throw new Exception($error);
        }
        return $this->_webpage;
    }

    public function getHttpStatus() {
        return $this->_status;
    }

    public function __tostring() {
        return $this->_webpage;
    }

    public function request($httpMethod, $sPath, $aRequest = array()) {
        if ($httpMethod === 'GET') {
            return $this->get($sPath, $aRequest);
        }
        if ($httpMethod === 'POST') {
            return $this->post($sPath, $aRequest);
        }
    }

    public function get($sPath, $aRequest = array()) {
        $this->_post = false;
        $sPath .= '?' . http_build_query($aRequest);
        return $this->createCurl($sPath);
    }

    public function post($_param1, $_param2 = array()) {
        $sPath = $_param1;
        $aPost = $_param2;
        if (is_array($_param1)) {
            $sPath = '';
            $aPost = $_param1;
        }
        if ($this->_upload || is_string($aPost)) {
            $this->setPost($aPost);
            return $this->createCurl($sPath);
        }
        if (!is_array($aPost)) {
            throw new Exception('你输入的内容既不是string也不是array');
        }
        $this->setPost($aPost);
        return $this->createCurl($sPath);
    }

    public function post_json($sPath, $aPost) {
        $this->setHeader('Content-Type', 'application/json');
        return $this->post($sPath, $aPost);
    }

    private function UriEncode($str) {
        $str = urlencode($str);
        $str = str_replace('+', '%20', $str);
        return $str;
    }

    private function UriEncodeExceptSlash($str) {
        /*
         * 与UriEncode() 类似，区别是斜杠（/）不做编码。
         * 一个简单的实现方式是先调用UriEncode()，
         * 然后把结果中所有的`%2F`都替换为`/`
         */
        $str = $this->UriEncode($str);
        $str = str_replace('%2F', '/', $str);
        return $str;
    }

    private function CURLOPT_HTTPHEADER() {
        $ret = array();
        foreach ($this->_header as $key => $value) {
            $ret[] = $key . ': ' . $value;
        }
        // print_r($ret);
        return $ret;
    }

    private function headerNameFormat($_name) {
        $_name2 = strtolower($_name);
        $name = preg_replace_callback("|([A-Za-z]+)|", function($matches) {
            return ucfirst($matches[1]);
        }, $_name2);
        return $name;
    }

    private function getHeader($_name) {
        $name = $this->headerNameFormat($_name);
        if (!isset($this->_header[$name])) {
            return;
        }
        return $this->_header[$name];
    }

    public function setHeader($_name, $value) {
        $name = $this->headerNameFormat($_name);
        $this->_header[$name] = $value;
    }

    private function getHeaderBody($sData, &$aHeader, &$sBody) {
        $aHeader = array();
        $sDivSign = "\r\n\r\n";
        $iRet = 0;
        while (1) {
            $iRet = strpos($sData, $sDivSign, $iRet);
            if ($iRet === FALSE) {
                $sBody = $sData;
                return;
            }
            $sHeader = substr($sData, 0, $iRet);
            if ($sHeader === 'HTTP/1.1 100 Continue') {
                $iRet += strlen($sDivSign);
                continue;
            }
            break;
        }
        $aHeader = explode("\r\n", $sHeader);
        $sBody = substr($sData, $iRet + strlen($sDivSign));
    }

    public function upload($bool = TRUE) {
        $this->_upload = $bool;
    }

    public function setTimeout($_timeout = 10) {
        $this->_timeout = $_timeout;
    }

    public function setMethod($method) {
        $this->_method = $method;
    }

    public function setSocks5($socks5) {
        $this->_socks5 = $socks5;
    }

}
