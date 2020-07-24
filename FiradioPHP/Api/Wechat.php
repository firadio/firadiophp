<?php

namespace FiradioPHP\Api;

use \DOMDocument;
use \FiradioPHP\Api\Wechat\Response;
use \FiradioPHP\Api\Wechat\Wxpay;

use FiradioPHP\Socket\Curl;

class Wechat {

    private $aConfig, $sRawContent;
    public $request = array();

    public function __construct($conf) {
        $this->aConfig = $conf;
        unset($this->aConfig['access_token_created']);
        $this->oCurl = new Curl();
    }

    private function error($errmsg, $errcode) {
        $ex = new \Exception($errmsg);
        $ex->sCode = $errcode;
        throw $ex;
    }

    public function getSignature($timestamp, $nonce) {
        // 取得signature
        $tmpArr = array($nonce, $timestamp, $this->aConfig['token']);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        return sha1($tmpStr);
    }

    public function loadXML($sRawContent) {
        $xml_tree = new DOMDocument();
        $xml_tree->loadXML($sRawContent);
        $nodes = $xml_tree->getElementsByTagName('Encrypt');
        if ($nodes->length > 0) {
            $encrypt = $nodes->item(0)->nodeValue;
            if (!empty($encrypt)) {
                $pc = new \FiradioPHP\Crypt\Prpcrypt($this->aConfig['encodingAesKey']);
                //$encrypt = $oText->getstr1($oRes->sRawContent, '<Encrypt><![CDATA[', ']]></Encrypt>');
                $out = $pc->decrypt($encrypt, $this->aConfig['appId']);
                $this->sRawContent = $out[1];
                $xml_tree->loadXML($out[1]);
            }
        }
        $field = 'CreateTime,FromUserName,ToUserName';
        $field .= ',Content,MediaId,Recognition';
        $field .= ',MsgType,Event,EventKey';
        $field .= ',Latitude,Longitude,Precision';
        $aField = explode(',', $field);
        foreach ($aField as $sField) {
            $obj = $xml_tree->getElementsByTagName($sField);
            $this->request[$sField] = ($obj->length > 0) ? $obj->item(0)->nodeValue : '';
        }
    }

    public function getRawContent() {
        return $this->sRawContent;
    }

    public function getResponse($sContent = '') {
        if (empty($sContent)) {
            $response = new Response();
            $response->appendToXml('FromUserName', $this->request['ToUserName']);
            $response->appendToXml('ToUserName', $this->request['FromUserName']);
            $response->appendToXml('MsgType', 'transfer_customer_service');
            return $response->saveXML();
        } 
        if (strpos($sContent, 'voice:') === 0) {
            $sContent = substr($sContent, 6);
            return $this->getResponse2($sContent, 'voice');
        }
        if (strpos($sContent, 'image:') === 0) {
            $sContent = substr($sContent, 6);
            return $this->getResponse2($sContent, 'image');
        }
        return $this->getResponse2($sContent);
    }

    public function getResponseNews($Title = '图文消息标题', $Description = '图文消息描述', $Url = 'http://wx.anan.cc', $PicUrl = 'http://wx.anan.cc/firadio/images/logo360x200.png') {
        $response = new Response();
        $response->appendToXml('FromUserName', $this->request['ToUserName']);
        $response->appendToXml('ToUserName', $this->request['FromUserName']);
        $response->appendToXml('MsgType', 'news');
        $Articles = array();
        $Article = array();
        $Article['Title'] = $Title;
        $Article['Description'] = $Description;
        $Article['PicUrl'] = $PicUrl;
        $Article['Url'] = $Url;
        $Articles[] = $Article;
        $response->appendToXml('ArticleCount', count($Articles));
        $response->appendItems('Articles', $Articles);
        $ret = $response->saveXML();
        return $ret;
    }

    public function getResponse2($sContent, $MsgType = 'Text') {
        $response = new Response();
        $response->appendToXml('FromUserName', $this->request['ToUserName']);
        $response->appendToXml('ToUserName', $this->request['FromUserName']);
        $response->appendToXml('MsgType', $MsgType);
        if ($MsgType === 'voice') {
            $response->appendToXml2('Voice', 'MediaId', $sContent);
        } else if ($MsgType === 'image') {
            $response->appendToXml2('Image', 'MediaId', $sContent);
        } else {
            $response->appendToXml('Content', $sContent);
        }
        $ret = $response->saveXML();
        return $ret;
    }

    private function retDataFromCurl($sUrl, $aGet, $aPost = NULL) {
        $oCurl = new Curl();
        $oCurl->setUrlPre($sUrl);
        $oCurl->setParam($aGet);
        if (is_array($aPost)) {
            $sPost = json_encode($aPost, JSON_UNESCAPED_UNICODE);
            $oCurl->setPost($sPost);
        }
        $jsonStr = $oCurl->execCurl();
        $jsonArr = json_decode($jsonStr, TRUE);
        if (!empty($jsonArr['errcode']) && isset($jsonArr['errmsg'])) {
            print_r($jsonArr);
            $this->error($jsonArr['errmsg'], $jsonArr['errcode']);
        }
        return $jsonArr;
    }

    private function access_token() {
        if (isset($this->aConfig['access_token_created']) && time() - $this->aConfig['access_token_created'] < 7200) {
            return $this->aConfig['access_token'];
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/token';
        $get = array();
        $get['grant_type'] = 'client_credential';
        $get['appid'] = $this->aConfig['appId'];
        $get['secret'] = $this->aConfig['appSecret'];
        $jsonArr = $this->retDataFromCurl($url, $get);
        if (empty($jsonArr['access_token'])) {
            throw new \Exception('access_token()' . $jsonStr);
            return;
        }
        $this->aConfig['access_token'] = $jsonArr['access_token'];
        $this->aConfig['access_token_created'] = time();
        return $jsonArr['access_token'];
    }

    public function media_upload($data, $mimeType = 'image/jpeg') {
        $get = array();
        $get['access_token'] = $this->access_token();
        $get['type'] = 'image';
        $post = array();
        $tmpPath = 'tmp~';
        file_put_contents($tmpPath, $data);
        $post['media'] = curl_file_create($tmpPath, $mimeType, '1.jpg');
        $url = 'http://file.api.weixin.qq.com/cgi-bin/media/upload';
        $this->oCurl->setUrlPre($url);
        $this->oCurl->upload(TRUE);
        $jsonStr = $this->oCurl->post($get, $post);
        $this->oCurl->upload(FALSE);
        $jsonArr = json_decode($jsonStr, TRUE);
        if (empty($jsonArr['media_id'])) {
            print_r($jsonArr);
            return;
        }
        return $jsonArr;
    }

    public function menu_get() {
        $get = array();
        $get['access_token'] = $this->access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/get';
        return $this->retDataFromCurl($url, $get);
    }

    public function menu_create($aTop) {
        $data = array();
        $data['button'] = $aTop['menu']['button'];
        $get = array();
        // access_token() 要先执行，否则执行过的 setUrlPre() 会被覆盖
        $get['access_token'] = $this->access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create';
        return $this->retDataFromCurl($url, $get, $data);
    }

    public function get_current_selfmenu_info() {
        $get = array();
        $get['access_token'] = $this->access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/get_current_selfmenu_info';
        return $this->retDataFromCurl($url, $get);
    }

    public function menu_addconditional($data) {
        $get = array();
        $get['access_token'] = $this->access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/addconditional';
        return $this->retDataFromCurl($url, $get, $data);
    }

    public function menu_delconditional($menuid) {
        $aData = array();
        $aData['menuid'] = $menuid;
        $get = array();
        $get['access_token'] = $this->access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/delconditional';
        return $this->retDataFromCurl($url, $get, $aData);
    }

    public function Wxpay() {
        return new Wxpay($this->aConfig);
    }

    public function getTokenByCode($code) {
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code';
        $url = str_replace('APPID', $this->aConfig['appId'], $url);
        $url = str_replace('SECRET', $this->aConfig['appSecret'], $url);
        $url = str_replace('CODE', $code, $url);
        $this->oCurl->setUrlPre($url);
        $get = array();
        $this->oCurl->setParam($get);
        $jsonStr = $this->oCurl->execCurl();
        if (empty($jsonStr)) exit('<font size=7>请等待...</font><script>location.href="http://' . $GLOBALS['safedomain'] . '";</script>');
        $jsonArr = json_decode($jsonStr, true);
        if (!empty($jsonArr['errcode'])) {
            $this->error($jsonArr['errmsg'], $jsonArr['errcode']);
            return;
        }
        /*
         * varchar(110) access_token
         * int(10)      expires_in = 7200
         * varchar(110) refresh_token
         * varchar(30)  openid
         * varchar(16)  scope
         */
        return $jsonArr;
    }

    private function nonce_str() {
        return md5(mt_rand() . uniqid() . microtime());
    }

    private function jsapi_getticket() {
        $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=ACCESS_TOKEN&type=jsapi';
        $url = str_replace('ACCESS_TOKEN', $this->access_token(), $url);
        $this->oCurl->setUrlPre($url);
        $get = array();
        $this->oCurl->setParam($get);
        $jsonStr = $this->oCurl->execCurl();
        $jsonArr = json_decode($jsonStr, true);
        if (!empty($jsonArr['errcode'])) {
            $this->error($jsonArr['errmsg'], $jsonArr['errcode']);
            return;
        }
        return $jsonArr['ticket'];
    }

    public function jsapi_config($url) {
        $config = array();
        $config['debug'] = TRUE; // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
        $config['appId'] = $this->aConfig['appId']; // 必填，公众号的唯一标识
        $config['timestamp'] = '' . time(); // 必填，生成签名的时间戳
        $config['nonceStr'] = $this->nonce_str(); // 必填，生成签名的随机串
        $config['signature'] = $this->jsapi_config_signature($config, $url); // 必填，签名
        $config['jsApiList'] = array(); // 必填，需要使用的JS接口列表
        $config['jsApiList'][] = 'chooseWXPay';
        return $config;
    }

    private function jsapi_config_signature($config, $url) {
        $data = array();
        $data['jsapi_ticket'] = $this->jsapi_getticket();
        $data['noncestr'] = $config['nonceStr'];
        $data['timestamp'] = $config['timestamp'];
        $data['url'] = $url;
        $arr = array();
        ksort($data);
        foreach ($data as $k => $v) {
            $arr[] = $k . '=' . $v;
        }
        return sha1(implode('&', $arr));
    }

}


