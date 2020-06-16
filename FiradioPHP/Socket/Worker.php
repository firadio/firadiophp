<?php

namespace FiradioPHP\Socket;

class Worker {

    private $url = 'http://127.0.0.1';
    private $oConfig;

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
        return array();
    }

    private function getPath($sUrl) {
        $kvUrl = parse_url($sUrl);
        if (!isset($kvUrl['query'])) {
            return;
        }
        $kvQuery = array();
        parse_str($kvUrl['query'], $kvQuery);
        if (!isset($kvQuery['service'])) {
            return;
        }
        return str_replace('.', '/', $kvQuery['service']);
    }

    private function getIpAddr($kvReqHeader) {
        if (isset($kvReqHeader['x-forwarded-for'])) {
            //取得GO服务器传过来的IP
            return $kvReqHeader['x-forwarded-for'];
        }
        if (isset($kvReqHeader['x-remote-address'])) {
            //取得NodeJS服务器传过来的IP
            $ip = $kvReqHeader['x-remote-address'];
            if (strpos($ip, '::ffff:') !== FALSE) {
                $ip = substr($ip, 7);
            }
            return $ip;
        }
    }

    private function getResBody($kvReqHeader, $sReqBody) {
        $IPADDR = $this->getIpAddr($kvReqHeader);
        $sPath = $this->getPath($kvReqHeader['url']);
        $this->consoleLog($this->ipaddr_format($IPADDR) . ' ' . $sPath);
        $oRes = new \FiradioPHP\Routing\Response();
        $oRes->setParam('IPADDR', $IPADDR);
        $oRes->setParam('sUserAgent', $kvReqHeader['user-agent']);
        $oRes->path = $sPath;
        $oRes->aRequest = $this->getParam($sReqBody);
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
                return($sMsg);
            }
            if ($sCode === 'ActionNotFound') {
                $sMsg = '错误：【service参数值】已改变，请到API调试页获取新的service参数值';
            }
            $oRes->assign('ret', $iCode);
            $oRes->assign('code', (string) $sCode);
            $oRes->assign('msg', $sMsg);
        }
        $ret = json_encode($oRes->aResponse);
        echo ' [' . number_format($oRes->getExecTime() * 1000, 2) . 'ms]';
        return $ret;
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
            $sResBody = $this->getResBody($aReqHeader, $sReqBody);
            $aResHeader = array();
            $aResHeader[] = "client-queueid: {$aReqHeader['client-queueid']}";
            //$aResHeader[] = "Content-Type: text/plain";
            $aResHeader[] = 'Content-Type: application/json; charset=UTF-8';
            //第4步：将本次处理好的queueid提交上去
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aResHeader);
        }
        echo microtime(TRUE) - $t1;
        curl_close($ch);
    }

}
