<?php

namespace FiradioPHP\Api;

use FiradioPHP\F;
use FiradioPHP\Socket\Curl;
use \DateTime;
use \DateTimeZone;

class Bce {

    private $aConfig;
    public $oCurl;

    public function __construct($conf = array()) {
        $this->aConfig = $conf;
        $sUrl = 'http://lss.bj.baidubce.com';
        $this->oCurl = new Curl($sUrl);
        // $this->oCurl->postFormat = 'json';
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

    public function updateHeader($aCanonicalHeaders, $httpMethod, $CanonicalURI, $CanonicalQueryString = '') {
        /*
         * 可以参考《百度云》官方文档
         * https://cloud.baidu.com/doc/Reference/AuthenticationMechanism.html
         */
        $bceAuthPrefix = 'bce-auth-v1';
        $UTC = new DateTime();
        $UTC->setTimeZone(new DateTimeZone('UTC'));
        /*
         * timestamp：签名生效UTC时间，格式为yyyy-mm-ddThh:mm:ssZ，例如：2015-04-27T08:23:49Z，默认值为当前时间。
         */
        $timestamp = $UTC->format('Y-m-d') . 'T' . $UTC->format('H:i:s') . 'Z';
        $aCanonicalHeaders['x-bce-date'] = $timestamp;
        $accessKeyId = $this->aConfig['app_key']; //AK
        $SK = $this->aConfig['app_secret']; //Secret Access Key
        /*
         * expirationPeriodInSeconds 是 签名有效期限
         * 从timestamp所指定的时间开始计算，时间为秒，默认值为1800秒（30）分钟。
         */
        $expirationPeriodInSeconds = 1800;
        /*
         * 1.2 生成 CanonicalRequest
         * CanonicalRequest = HTTP Method + "\n" + CanonicalURI + "\n" + CanonicalQueryString + "\n" + CanonicalHeaders
         */
        $signedHeaders = $CanonicalHeaders = array();
        ksort($aCanonicalHeaders);
        foreach ($aCanonicalHeaders as $key => $value) {
            $key = strtolower(trim($key));
            $value = $this->UriEncode(trim($value));
            $signedHeaders[] = $key;
            $CanonicalHeaders[] = $key . ':' . $value;
        }
        //sort($signedHeaders);
        $signedHeaders = implode(';', $signedHeaders);
        //sort($CanonicalHeaders);
        $CanonicalHeaders = implode("\n", $CanonicalHeaders);
        $CanonicalURI = $this->UriEncodeExceptSlash($CanonicalURI);
        $CanonicalRequest = $httpMethod . "\n" . $CanonicalURI . "\n" . $CanonicalQueryString . "\n" . $CanonicalHeaders;
        /*
         * 1.3 生成 SigningKey
         * authStringPrefix代表认证字符串的前缀部分，即：bce-auth-v1/{accessKeyId}/{timestamp}/{expirationPeriodInSeconds}
         */
        $authStringPrefix = $bceAuthPrefix . '/' . $accessKeyId . '/' . $timestamp . '/' . $expirationPeriodInSeconds;
        //var_dump($CanonicalRequest);
        $SigningKey = hash_hmac('SHA256', $authStringPrefix, $SK);
        /*
         * 1.4 生成Signature
         * 使用 HMACSHA256 算法，SignKey，CanonicalRequest 生成最终签名
         */
        $Signature = hash_hmac('SHA256', $CanonicalRequest, $SigningKey);
        /*
         * 1.5 生成认证字符串 Authorization
         * 认证字符串 = bce-auth-v1/{accessKeyId}/{timestamp}/{expirationPeriodInSeconds}/{signedHeaders}/{signature}
         */
        $Authorization = $bceAuthPrefix . '/' . $accessKeyId . '/' . $timestamp . '/' . $expirationPeriodInSeconds . '/' . $signedHeaders . '/' . $Signature;
        //$aCanonicalHeaders['Authorization'] = $Authorization;
        foreach ($aCanonicalHeaders as $key => $value) {
            $this->oCurl->setHeader($key, $value);
        }
        $this->oCurl->setHeader('Authorization', $Authorization);
    }

    public function setAppKey($sAppKey) {
        $this->oCurl->setHeader('AppKey', $sAppKey);
    }

    public function setAppSecret($sAppSecret) {
        $sNonce = mt_rand();
        $sCurTime = time();
        $sCheckSum = $this->getCheckSum($sAppSecret, $sNonce, $sCurTime);
        $this->oCurl->setHeader('Nonce', $sNonce);
        $this->oCurl->setHeader('CurTime', $sCurTime);
        $this->oCurl->setHeader('CheckSum', $sCheckSum);
    }

    public function getCheckSum($sAppSecret, $sNonce, $sCurTime) {
        return sha1($sAppSecret . $sNonce . $sCurTime);
    }

    private function request($httpMethod, $sPath, $aRequest = array()) {
        $aCanonicalHeaders = array();
        $aCanonicalHeaders['Content-Type'] = 'application/json';
        $aCanonicalHeaders['Host'] = 'lss.bj.baidubce.com';
        $aCanonicalHeaders['x-bce-meta-data-tag'] = 'description';
        $aCanonicalHeaders['x-bce-meta-data'] = 'my meta data';
        $CanonicalQueryString = '';
        $this->updateHeader($aCanonicalHeaders, $httpMethod, $sPath, $CanonicalQueryString);
        $ret = $this->oCurl->request($httpMethod, $sPath, $aRequest);
        if ($this->oCurl->getHttpStatus() !== 200) {
            print_r($this->oCurl->response_header);
            F::error('return HttpStatus=' . $this->oCurl->getHttpStatus());
            return;
        }
        $ret = json_decode($ret, true);
        return $ret;
    }

    public function channel_create($description) {
        /*
         * 创建会话
         * https://cloud.baidu.com/doc/LSS/API.html#.E5.88.9B.E5.BB.BA.E4.BC.9A.E8.AF.9D
         */
        $aPost = array();
        $aPost['description'] = $description;
        $result = $this->request('POST', '/v5/session', $aPost);
        $ret = array();
        $ret['tp_cid'] = $result['sessionId'];
        $ret['tp_name'] = $result['description'];
        $ret['push_url'] = $result['publish']['pushUrl'];
        $ret['pull_rtmp'] = $result['play']['rtmpUrl'];
        $ret['pull_http'] = $result['play']['flvUrl'];
        $ret['pull_hls'] = $result['play']['hlsUrl'];
        return $ret;
    }

    public function channel_update($iCid, $sName, $iType = 0) {
        //2.2 修改频道
        $aPost = array();
        $aPost['cid'] = $iCid;
        $aPost['name'] = $sName;
        $aPost['type'] = $iType;
        $ret = $this->post('/channel/update', $aPost);
        return $ret;
    }

    public function channel_delete($iCid) {
        //2.3 删除频道
        $aPost = array();
        $aPost['cid'] = $iCid;
        $ret = $this->post('/channel/delete', $aPost);
        return $ret;
    }

    public function channel_stats($iCid) {
        /*
         * 查询会话
         * https://cloud.baidu.com/doc/LSS/API.html#.E6.9F.A5.E8.AF.A2.E4.BC.9A.E8.AF.9D
         */
        $result = $this->request('GET', '/v5/session/' . $iCid);
        // return $result;
        $ret = array();
        $ret['tp_cid'] = $result['sessionId'];
        $ret['tp_name'] = $result['description'];
        $ret['push_url'] = $result['publish']['pushUrl'];
        $ret['pull_rtmp'] = $result['play']['rtmpUrl'];
        $ret['pull_http'] = $result['play']['flvUrl'];
        $ret['pull_hls'] = $result['play']['hlsUrl'];
        $ret['status'] = 0;
        $status = $result['status'];
        $streamingStatus = isset($result['streamingStatus']) ? $result['streamingStatus'] : NULL;
        if ($status === 'ONGOING' && $streamingStatus === 'STREAMING') {
            $ret['status'] = 1;
        }
        return $ret;
    }

    public function channel_list($iRecords = 10, $iPnum = 1, $sOfield = '', $iSort = 1) {
        /*
         * 会话列表
         * https://cloud.baidu.com/doc/LSS/API.html#.E4.BC.9A.E8.AF.9D.E5.88.97.E8.A1.A8
         */
        $aPost = array();
        $aPost['records'] = $iRecords;
        $aPost['pnum'] = $iPnum;
        $aPost['ofield'] = $sOfield;
        $aPost['sort'] = $iSort;
        $ret = $this->request('GET', '/v5/session', $aPost);
        $retRows = array();
        $hls_domain = 'hb2vmcdh1g874xd8ixy.exp.bcelive.com';
        $push_domain = 'push.bcelive.com';
        foreach ($ret['sessions'] as $row) {
            $retRow = array();
            $retRow['pull_rtmp'] = 'rtmp://' . $row['playDomain'] . '/' . $row['sessionId'];
            $retRow['pull_http'] = 'http://' . $row['playDomain'] . '/' . $row['sessionId'] . '.flv';
            $retRow['pull_hls'] = 'http://' . $hls_domain . '/' . $row['sessionId'] . '/live.m3u8';
            $retRow['tp_cid'] = $row['sessionId'];
            $retRow['tp_name'] = $row['description'];
            $retRow['push_stream'] = $row['publish']['pushStream'];
            $retRow['push_url'] = 'rtmp://' . $push_domain . '/live/' . $retRow['push_stream'];
            $retRows[] = $retRow;
        }
        return $ret;
    }

    public function channel_address($iCid) {
        //2.6 重新获取推流地址
        $aPost = array();
        $aPost['cid'] = $iCid;
        $ret = $this->post('/address', $aPost);
        return $ret;
    }

    private function error($message, $title = 'Netease提示') {
        $ex = new \Exception($message, -2);
        $ex->title = $title;
        throw $ex;
    }

}
