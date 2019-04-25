<?php

namespace FiradioPHP\Api\Wechat;

use FiradioPHP\F;
use FiradioPHP\Socket\Curl;

class Qyapi {

    private $aConfig;
    private $aCache;
    private $oCurl;

    public function __construct($conf) {
        $this->aConfig = $conf['config'];
        $this->aCache = F::$oConfig->getInstance('cache');
        $this->aCache->setPrefix($conf['name'] . '_');
        $sUrl = 'https://' . $this->aConfig['host'] . '/cgi-bin/';
        $this->oCurl = new Curl($sUrl);
    }

    private function post_raw($path, $aPost = array()) {
        $ret = $this->oCurl->post($path, $aPost);
        $ret = json_decode($ret, true);
        return $ret;
    }

    private function post($path, $aPost = array()) {
        $path .= '?access_token=' . $this->gettoken();
        $ret = $this->oCurl->post($path, $aPost);
        $ret = json_decode($ret, true);
        return $ret;
    }

    private function gettoken() {
        if ($expired = $this->aCache->get('expired')) {
            if (time() < $expired) {
                // 没过期的话就获取原来的
                return $this->aCache->get('access_token');
            }
        }
        $path = '/gettoken?corpid=ID&corpsecret=SECRET';
        $path = str_replace('ID', $this->aConfig['corpid'], $path);
        $path = str_replace('SECRET', $this->aConfig['corpsecret'], $path);
        $ret = $this->post_raw($path);
        if ($ret['errcode'] !== 0) {
            throw new Exception($ret);
        }
        if ($ret['errmsg'] !== 'ok') {
            throw new Exception($ret);
        }
        $this->aCache->set('access_token', $ret['access_token']);
        $this->aCache->set('expired', time() + $ret['expires_in']);
        return $ret['access_token'];
    }

    public function appchat_create($chatid = 'feieryun') {
        $aPost = array();
        $aPost['name'] = '飞儿云平台的群';
        $aPost['owner'] = 'XiangXiSheng';
        $aPost['userlist'] = 'XiangXiSheng,XiangXiSheng';
        $aPost['chatid'] = $chatid;
        $ret = $this->post('/appchat/create', $aPost);
        return $ret;
    }


}
