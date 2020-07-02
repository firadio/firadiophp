<?php

namespace FiradioPHP\Socket;

class Worker {

    private $url = 'http://127.0.0.1';
    private $oConfig;
    private $mUrlQuery = array();

    public function __construct($oConfig) {
        $this->oConfig = $oConfig;
    }

    public function run() {
        $this->curl_start();
    }

    public function setUrl($sUrl) {
        $this->url = $sUrl;
    }

    private function getParam($sRawContent) {
        if (substr($sRawContent, 0, 1) === '{') {
            return json_decode($sRawContent, true);
        }
        // 非JSON格式就按URL编码的Post进行解析        
        $mQuery = array();
        parse_str($sRawContent, $mQuery);
        return $mQuery;
    }

    private function getPath($sUrl) {
        $kvUrl = parse_url($sUrl);
        if (!isset($kvUrl['query'])) {
            return $kvUrl['path'];
        }
        parse_str($kvUrl['query'], $this->mUrlQuery);
        if (!isset($this->mUrlQuery['service'])) {
            return $kvUrl['path'];
        }
        return str_replace('.', '/', $this->mUrlQuery['service']);
    }

    private function getIpAddr($mReqHeader) {
        if (isset($mReqHeader['x-forwarded-for'])) {
            //取得GO服务器传过来的IP
            return $mReqHeader['x-forwarded-for'];
        }
        if (isset($mReqHeader['x-remote-address'])) {
            //取得NodeJS服务器传过来的IP
            $ip = $mReqHeader['x-remote-address'];
            if (strpos($ip, '::ffff:') !== FALSE) {
                $ip = substr($ip, 7);
            }
            return $ip;
        }
    }

    private function getResponse($mReqHeader, $sReqBody) {
        $IPADDR = $this->getIpAddr($mReqHeader);
        $sPath = $this->getPath($mReqHeader['url']);
        $this->consoleLog($this->ipaddr_format($IPADDR) . ' ' . $sPath);
        $oRes = new \FiradioPHP\Routing\Response();
        $oRes->setParam('IPADDR', $IPADDR);
        $oRes->setParam('sUserAgent', $mReqHeader['user-agent']);
        $oRes->setParam('sRawUrl', $mReqHeader['url']);
        $oRes->setParam('sRawContent', $sReqBody);
        $oRes->path = $sPath;
        $oRes->putRequest($this->mUrlQuery);
        $oRes->putRequest($this->getParam($sReqBody));
        $oRes->mRequestHeader = $mReqHeader;
        $oRes->assign('ret', 0);
        try {
            $this->oConfig->aInstances['router']->execAction($oRes);
        } catch (\Exception $ex) {
            $sCode = $iCode = $ex->getCode();
            if (!empty($ex->sCode)) {
                $sCode = $ex->sCode;
            }
            $sMsg = $ex->getMessage();
            $oRes->debug('msg', $sMsg);
            if ($sCode === 'end') {
                $mResHeader = $oRes->getResponseHeaders();
                return array($mResHeader, $sMsg);
            }
            if ($sCode === 'ActionNotFound') {
                $sMsg = '错误：【service参数值】已改变，请到API调试页获取新的service参数值';
            }
            $oRes->assign('ret', $iCode);
            $oRes->assign('code', (string) $sCode);
            $oRes->assign('msg', $sMsg);
        }
        $mResHeader = $oRes->getResponseHeaders();
        $sResBody = $oRes->getResponseBody();
        echo ' [' . number_format($oRes->getExecTime() * 1000, 2) . 'ms]';
        return array($mResHeader, $sResBody);
    }

    private function ipaddr_format($IPADDR) {
        $aIpNums = explode('.', $IPADDR);
        $aRet = array();
        foreach ($aIpNums as $iIpNum) {
            $aRet[] = str_pad($iIpNum, 3, '0', STR_PAD_LEFT);
        }
        return implode('.', $aRet);
    }

    private function consoleLog($sMsg) {
        echo "\r\n" . date('Y-m-d H:i:s') . ' ' . $sMsg;
    }

    private function getHeaderBody($sData) {
        $sDivSign = "\r\n\r\n";
        $iRet = 0;
        while (1) {
            $iRet = strpos($sData, $sDivSign, $iRet);
            if ($iRet === FALSE) {
                return array(array(), $sData);
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
        $aHeaderNew = array();
        foreach ($aHeader as $sLine) {
            $i = strpos($sLine, ': ');
            if ($i === FALSE) {
                continue;
            }
            $key = strtolower(substr($sLine, 0, $i));
            $value = substr($sLine, $i + 2);
            $aHeaderNew[$key] = $value;
        }
        return array($aHeaderNew, $sBody);
    }

    private function curl_start() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'php-worker-v1');
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);

        $t1 = microtime(TRUE);
        $sResBody = '';
        while (TRUE) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $sResBody);
            //第1步：获取客户请求（阻塞等待）
            $curl_data = curl_exec($ch);
            if ($curl_data === FALSE) {
                $curl_errno = curl_errno($ch);
                $curl_error = curl_error($ch);
                if (\CURLE_GOT_NOTHING === $curl_errno) {
                    $this->consoleLog("[EmptyReply] {$curl_error}");
                    continue;
                }
                $this->consoleLog("[Error] {$curl_errno} {$curl_error}");
                continue;
            }
            //第2步：取得客户的请求Header与Body
            list($aReqHeader, $sReqBody) = $this->getHeaderBody($curl_data);
            if (empty($aReqHeader['client-queueid'])) {
                $this->consoleLog("[Error] no client-queueid in aReqHeader");
                var_dump($curl_data);
                continue;
            }
            //第3步：处理客户的请求，并返回新的Body请求
            list($mResHeader, $sResBody) = $this->getResponse($aReqHeader, $sReqBody);
            $mResHeader['client-queueid'] = $aReqHeader['client-queueid'];
            $mResHeader['worker-msg-lastseq'] = $aReqHeader['worker-msg-lastseq'];
            //$mResHeader['Content-Type'] = 'text/plain';
            if (!isset($mResHeader['Content-Type'])) {
                $mResHeader['Content-Type'] = 'application/json; charset=UTF-8';
            }
            $aResHeader = array();
            foreach ($mResHeader as $sResHeaderName => $oResHeader) {
                if (is_array($oResHeader)) {
                    foreach ($oResHeader as $sResHeader) {
                        $aResHeader[] = $sResHeaderName . ': ' . $sResHeader;
                    }
                    continue;
                }
                $aResHeader[] = $sResHeaderName . ': ' . $oResHeader;
            }
            //第4步：将本次处理好的queueid提交上去
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aResHeader);
        }
        echo microtime(TRUE) - $t1;
        curl_close($ch);
    }

}
