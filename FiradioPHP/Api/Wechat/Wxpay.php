<?php

namespace FiradioPHP\Api\Wechat;

use \DOMDocument;

use \FiradioPHP\Api\Wechat\Response;

use FiradioPHP\Socket\Curl;

class Wxpay {

    private $aConfig;
    public $request = array();

    public function __construct($conf) {
        $this->aConfig = $conf;
        $this->oCurl = new Curl();
        $this->oCurl->sslcert_file = APP_ROOT . '/config/wechat/apiclient_cert.pem';
        $this->oCurl->sslkey_file = APP_ROOT . '/config/wechat/apiclient_key.pem';
    }

    public function sign(&$data) {
        $data['nonce_str'] = mt_rand();
        $data2 = $data;
        ksort($data2);
        $data2['key'] = $this->aConfig['mchKey'];
        $arr = array();
        foreach ($data2 as $k => $v) {
            $arr[] = $k . '=' . $v;
        }
        $data['sign'] = strtoupper(md5(implode('&', $arr)));
    }

    public function sendredpack() {
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
        $this->oCurl->setUrlPre($url);
        $data = array();
        $data['mch_billno'] = 'FIR' . date('YmdHis');
        $data['mch_id'] = $this->aConfig['mchId'];
        $data['wxappid'] = $this->aConfig['appId'];
        $data['send_name'] = '飞儿云平台';
        $data['re_openid'] = 'o6fZl0SipjGyauypnix-KPp9ghi8';
        $data['total_amount'] = 100;
        $data['total_num'] = 1; // total_num必须等于1
        $data['wishing'] = '红包祝福语';
        $data['client_ip'] = '127.0.0.1';
        $data['act_name'] = '飞儿云红包';
        $data['remark'] = '飞儿云红包';
        $data['scene_id'] = 'PRODUCT_4'; //PRODUCT_4:企业内部福利
        //$data['risk_info'] = 11;
        $this->sign($data);
        $arr = array();
        $arr['xml'] = array();
        $arr['xml'][0] = array();
        foreach ($data as $k => $v) {
            $arr['xml'][0][$k] = array(array('#cdata-section' => $v));
        }
        $xml = $this->array2dom($arr, new DOMDocument())->saveXML();
        //print_r($xml);
        $this->oCurl->setPost($xml);
        $res_xml = $this->oCurl->createCurl();
        $xml_tree = new DOMDocument();
        $xml_tree->loadXML($res_xml);
        //$return_msg = $xml_tree->getElementsByTagName('return_msg')->item(0)->nodeValue;
        $arr = $this->dom2array($xml_tree);
        //print_r($arr);
        echo $arr['xml'][0]['return_msg'][0]['#cdata-section'];
        exit;
    }

    public function dom2array($node) {
        //print $node->nodeType.'<br/>';
        $retArr = array();
        if ($node->hasAttributes()) {
            $attributes = $node->attributes;
            if (!is_null($attributes)) {
                $retArr['@attributes'] = array();
                foreach ($attributes as $index=>$attr) {
                    $retArr['@attributes'][$attr->name] = $attr->value;
                }
            }
        }
        if ($node->hasChildNodes()) {
            $children = $node->childNodes;
            $clen = $children->length;
            for ($i=0; $i < $clen; $i++) {
                $child = $children->item($i);
                $nodeName = $child->nodeName;
                if ($clen > 1 && $nodeName === '#text') {
                    continue;
                }
                $s = $this->dom2array($child);
                if (is_string($s)) {
                    $retArr[$nodeName] = $s;
                } else if (is_array($s)) {
                    if (!isset($retArr[$nodeName])) $retArr[$nodeName] = array();
                    $retArr[$nodeName][] = $s;
                }
            }
        }
        if (count($retArr) === 0) return $node->nodeValue;
        return $retArr;
    }

    public function array2dom($arr, $dom_root, $dom_sub = NULL) {
        //print_r($arr);
        if (is_null($dom_sub)) $dom_sub = $dom_root;
        foreach ($arr as $k => $v) {
            if ($k === '#cdata-section') {
                $dom_sub->appendChild($dom_root->createCDATASection($v));
            } else
            if ($k === '#text') {
                $dom_sub->appendChild($dom_root->createTextNode($v));
            } else
            if (is_array($v)) {
                $dom_sub1 = $dom_sub;
                if (is_string($k)) {
                    $dom_sub1 = $dom_sub->appendChild($dom_root->createElement($k));
                }
                $this->array2dom($v, $dom_root, $dom_sub1);
            }
        }
        return $dom_root;
    }
}

