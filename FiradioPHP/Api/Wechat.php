<?php

namespace FiradioPHP\Api;

use \DOMDocument;
use \FiradioPHP\Api\Wechat\Response;
use FiradioPHP\Socket\Curl;

class Wechat {

    private $aConfig, $sRawContent;
    public $request = array();

    public function __construct($conf) {
        $this->aConfig = $conf;
        $this->oCurl = new Curl();
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
        $array_e = $xml_tree->getElementsByTagName('Encrypt');
        $encrypt = $array_e->item(0)->nodeValue;
        if (empty($encrypt)) {
            print_r($sRawContent);
            return;
        }
        $pc = new \FiradioPHP\Crypt\Prpcrypt($this->aConfig['encodingAesKey']);
        //$encrypt = $oText->getstr1($oRes->sRawContent, '<Encrypt><![CDATA[', ']]></Encrypt>');
        $out = $pc->decrypt($encrypt, $this->aConfig['appId']);
        $this->sRawContent = $out[1];
        $xml_tree->loadXML($out[1]);
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

    private function access_token() {
        if (isset($this->aConfig['access_token_created']) && time() - $this->aConfig['access_token_created'] < 7200) {
            return $this->aConfig['access_token'];
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/token';
        $this->oCurl->setUrlPre($url);
        //$this->oCurl->setHeader('Content-Type', 'application/x-www-form-urlencoded');
        $post = array();
        $post['grant_type'] = 'client_credential';
        $post['appid'] = $this->aConfig['appId'];
        $post['secret'] = $this->aConfig['appSecret'];
        $jsonStr = $this->oCurl->post('', $post);
        $jsonArr = json_decode($jsonStr, TRUE);
        if (empty($jsonArr['access_token'])) {
            print_r($jsonArr);
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

    public function menu_create($button) {
        $data = array();
        $data['button'] = $button;
        $post = json_encode($data, JSON_UNESCAPED_UNICODE);
        $get = array();
        $get['access_token'] = $this->access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create';
        $this->oCurl->setUrlPre($url);
        $jsonStr = $this->oCurl->post($get, $post);
        $jsonArr = json_decode($jsonStr, TRUE);
        return $jsonArr;
    }
}
