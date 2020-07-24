<?php

namespace FiradioPHP\Api\Wechat;

use \DOMDocument;

use \FiradioPHP\Api\Wechat\Response;

use FiradioPHP\Socket\Curl;

class Wxpay {

    private $aConfig;
    public $request = array();
    public $data = array();

    public function __construct($conf) {
        $this->aConfig = $conf;
        $this->oCurl = new Curl();
        $this->oCurl->sslcert_file = APP_ROOT . '/config/wechat/apiclient_cert.pem';
        $this->oCurl->sslkey_file = APP_ROOT . '/config/wechat/apiclient_key.pem';
    }

    private function nonce_str() {
        return md5(mt_rand() . uniqid() . microtime());
    }

    private function getSign($data) {
        unset($data['sign']);
        ksort($data);
        $data['key'] = $this->aConfig['mchKey'];
        $arr = array();
        foreach ($data as $k => $v) {
            $arr[] = $k . '=' . $v;
        }
        return strtoupper(md5(implode('&', $arr)));
    }

    private function sign(&$data) {
        $data['nonce_str'] = $this->nonce_str();
        $data['sign'] = $this->getSign($data);
    }

    public function array2xml($aReqData) {
        $aInput = array();
        $aInput['xml'] = array();
        $aInput['xml'][0] = array();
        foreach ($aReqData as $k => $v) {
            $aInput['xml'][0][$k] = array(array('#cdata-section' => $v));
        }
        $xml = $this->array2dom($aInput, new DOMDocument())->saveXML();
        return $xml;
    }

    private function xml2array($sXml) {
        $obj = simplexml_load_string($sXml, NULL, LIBXML_NOCDATA);
        if ($obj === FALSE) {
            return array();
        }
        return json_decode(json_encode($obj), TRUE);
    }

    private function retCurlPostData($url, $aReqData) {
        $this->oCurl->setUrlPre($url);
        $xml = $this->array2xml($aReqData);
        $this->oCurl->setPost($xml);
        $res_xml = $this->oCurl->execCurl();
        return $this->xml2array($res_xml);
    }

    private function return_code($arr) {
        $code = $arr['xml'][0]['return_code'][0]['#cdata-section'];
        if (isset($arr['xml'][0]['err_code'])) {
            $code = $arr['xml'][0]['err_code'][0]['#cdata-section'];
        }
        return $code;
    }

    private function return_msg($arr) {
        $msg = $arr['xml'][0]['return_msg'][0]['#cdata-section'];
        if (isset($arr['xml'][0]['err_code_des'])) {
            $msg = $arr['xml'][0]['err_code_des'][0]['#cdata-section'];
        }
        return $msg;
    }

    public function sendredpack() {
        // 发放红包接口 https://pay.weixin.qq.com/wiki/doc/api/tools/cash_coupon.php?chapter=13_4&index=3
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
        $this->oCurl->setUrlPre($url);
        $data = array();
        $data['mch_billno'] = 'FIR' . date('YmdHis');
        $data['mch_id'] = $this->aConfig['mchId'];
        $data['wxappid'] = $this->aConfig['appId'];
        $data['send_name'] = '飞儿云平台';
        $data['re_openid'] = 'o6fZl0SipjGyauypnix-KPp9ghi8';
        $data['total_amount'] = 1;
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
        echo $this->return_msg($arr);
        exit;
    }


    public function transfers() {
        // 企业付款 https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=14_2
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $this->oCurl->setUrlPre($url);
        $data = array();
        $data['mch_appid'] = $this->aConfig['appId'];
        $data['mchid'] = $this->aConfig['mchId'];
        $data['partner_trade_no'] = 'FIR' . date('YmdHis');
        $data['openid'] = 'o6fZl0SipjGyauypnix-KPp9ghi8';
        $data['check_name'] = 'NO_CHECK';
        $data['amount'] = 1;
        $data['desc'] = '测试';
        $data['spbill_create_ip'] = '127.0.0.1';
        $this->sign($data);
        $arr = array();
        $arr['xml'] = array();
        $arr['xml'][0] = array();
        foreach ($data as $k => $v) {
            $arr['xml'][0][$k] = array(array('#cdata-section' => $v));
        }
        $xml = $this->array2dom($arr, new DOMDocument())->saveXML();
        $this->oCurl->setPost($xml);
        $res_xml = $this->oCurl->createCurl();
        $xml_tree = new DOMDocument();
        $xml_tree->loadXML($res_xml);
        $arr = $this->dom2array($xml_tree);
        echo $this->return_msg($arr);
        exit;
    }


    public function refund($out_refund_no, $out_trade_no, $total_fee, $refund_fee) {
        // 申请退款 https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_4
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $this->oCurl->setUrlPre($url);
        $data = array();
        $data['appid'] = $this->aConfig['appId'];
        $data['mch_id'] = $this->aConfig['mchId'];
        //$data['transaction_id'] = '4200000510202002059720570566';
        $data['out_trade_no'] = $out_trade_no;
        $data['out_refund_no'] = $out_refund_no;
        $data['total_fee'] = $total_fee * 100; // 订单金额
        $data['refund_fee'] = $refund_fee * 100; // 退款金额
        //$data['refund_account'] = 'REFUND_SOURCE_RECHARGE_FUNDS'; // 可用余额退款
        $this->sign($data);
        $arr = array();
        $arr['xml'] = array();
        $arr['xml'][0] = array();
        foreach ($data as $k => $v) {
            $arr['xml'][0][$k] = array(array('#cdata-section' => $v));
        }
        $xml = $this->array2dom($arr, new DOMDocument())->saveXML();
        $this->oCurl->setPost($xml);
        $res_xml = $this->oCurl->execCurl();
        $xml_tree = new DOMDocument();
        $xml_tree->loadXML($res_xml);
        $arr = $this->dom2array($xml_tree);
        $ret = array();
        $ret['code'] = $this->return_code($arr);
        $ret['msg'] = $this->return_msg($arr);
        return $ret;
    }


    public function unifiedOrderJSAPI($out_trade_no, $body, $total_fee, $openid) {
        // 统一下单 https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_1
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $data = array();
        $data['appid'] = $this->aConfig['appId']; // 公众账号ID
        $data['mch_id'] = $this->aConfig['mchId']; // 商户号
        $data['device_info'] = 'WEB'; // 设备号
        $data['sign_type'] = 'MD5'; // 签名类型
        $data['body'] = $body; // 商品描述
        // $data['detail'] = $detail; // 商品详情
        // $data['attach'] = ''; // 附加数据
        $data['out_trade_no'] = $out_trade_no;
        $data['fee_type'] = 'CNY'; // 标价币种
        $data['total_fee'] = $total_fee * 100; // 订单金额
        $data['spbill_create_ip'] = '127.0.0.1';
        // $data['time_start'] = date('YmdHis'); // 交易起始时间
        // $data['time_expire'] = date('YmdHis', time() + 7200); // 交易结束时间
        // $data['goods_tag'] = ''; // 订单优惠标记
        $data['notify_url'] = $this->aConfig['notify_url']; // 通知地址
        $data['trade_type'] = 'JSAPI'; // 交易类型
        $data['openid'] = $openid; // 用户标识
        if (isset($this->data['unifiedorder']) && is_array($this->data['unifiedorder'])) {
            $data = array_merge($data, $this->data['unifiedorder']);
        }
        $this->data['unifiedorder'] = $data;
        $this->sign($data);
        return $this->retCurlPostData($url, $data);
    }

    public function jsapi_chooseWXPay($unifiedOrderRet) {
        $values = array();
        $values['appId'] = $unifiedOrderRet['appid'];
        $values['timeStamp'] = time(); // 支付签名时间戳，注意微信jssdk中的所有使用timestamp字段均为小写。但最新版的支付后台生成签名使用的timeStamp字段名需大写其中的S字符
        $values['nonceStr'] = $this->nonce_str(); // 支付签名随机串，不长于 32 位
        $values['package'] = 'prepay_id=' . $unifiedOrderRet['prepay_id']; // 统一支付接口返回的prepay_id参数值，提交格式如：prepay_id=\*\*\*）
        $values['signType'] = 'MD5'; // 签名方式，默认为'SHA1'，使用新版支付需传入'MD5'
        ksort($values);
        $urlParams = $this->ToUrlParams($values);
        $values['paySign'] = strtoupper(md5($urlParams . '&key=' . $this->aConfig['mchKey'])); // 支付签名
        $values['timestamp'] = $values['timeStamp'];
        unset($values['timeStamp']);
        return $values;
    }

    /**
     * 格式化参数格式化成url参数
     */
    private function ToUrlParams($values) {
        $buff = "";
        foreach ($values as $k => $v) {
            if($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    public function dom2array2($node) {
        $ret = array();
        $mXml = $this->dom2array($node);
        $mData = $mXml['xml'][0];
        foreach ($mData as $key => $value) {
            $ret[$key] = $value[0]['#cdata-section'];
        }
        return $ret;
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

    public function notify_check($sXml) {
        $aReq = $this->xml2array($sXml);
        if (empty($aReq['sign'])) {
            return FALSE;
        }
        if ($this->getSign($aReq) !== $aReq['sign']) {
            return FALSE;
        }
        $this->data['notify'] = $aReq;
        return TRUE;
    }
}

