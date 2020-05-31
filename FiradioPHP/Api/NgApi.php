<?php

namespace FiradioPHP\Api;

use FiradioPHP\F;
use FiradioPHP\Socket\Curl;

class NgApi {

    private $aConfig;
    private $oCurl;
    private $oCurl2;

    public function __construct($conf = array()) {
        $this->aConfig = $conf['config'];
        if (!empty($this->aConfig['api_url'])) {
            $this->oCurl = new Curl($this->aConfig['api_url']);
        }
        if (!empty($this->aConfig['api2_url'])) {
            $this->oCurl2 = new Curl($this->aConfig['api2_url']);
        }
    }

    public function api2($service, $aPost) {
        $aParam = array();
        $aParam['service'] = 'ngapi.' . $service;
        $this->oCurl2->setParam($aParam);
        $this->oCurl2->setPost($aPost);
        $this->oCurl2->postFormat = 'json';
        $this->oCurl2->setHeader('Content-Type', 'application/json');
        $sJson = $this->oCurl2->execCurl();
        $ret = json_decode($sJson, TRUE);
        if (!empty($ret['msg'])) {
            throw new \Exception($ret['msg'], $ret['code']);
        }
        return $sJson;
    }

    public function getGameCategory() {
        $aPost = array();
        $aPost['sign_key'] = $this->aConfig['sign_key'];
        $aPost['code'] = md5($this->aConfig['sign_key'] . $this->aConfig['api_account']);
        $sJson = $this->oCurl->post('/v1/game/category', $aPost);
        return(json_decode($sJson, TRUE));
    }

    public function getGameCode($plat_type, $category_id = NULL) {
        $aPost = array();
        $aPost['sign_key'] = $this->aConfig['sign_key'];
        $aPost['code'] = md5($this->aConfig['sign_key'] . $this->aConfig['api_account'] . $plat_type);
        $aPost['plat_type'] = $plat_type;
        if ($category_id !== NULL) {
            $aPost['category_id'] = $category_id;
        }
        $sJson = $this->oCurl->post('/v1/game/code', $aPost);
        $ret = json_decode($sJson, TRUE);
        if (!empty($ret['statusCode']) && $ret['statusCode'] !== '01') {
            throw new \Exception($ret['message'], $ret['statusCode']);
        }
        return($ret['data']);
    }

    public function getUserLoginUrl($username, $plat_type, $game_type, $game_code, $is_mobile_url = 1) {
        $sPlatType = strtolower($plat_type);
        $aPost = array();
        $aPost['sign_key'] = $this->aConfig['sign_key'];
        $code_text = $this->aConfig['sign_key'] . $this->aConfig['api_account'];
        $code_text .= $username . $sPlatType . $is_mobile_url;
        $aPost['code'] = md5($code_text);
        $aPost['username'] = $username;
        $aPost['plat_type'] = $sPlatType;
        $aPost['game_type'] = $game_type;
        $aPost['game_code'] = $game_code;
        $aPost['is_mobile_url'] = $is_mobile_url; //是否手机登录 【1是】【0不是】
        //$aPost['demo'] = 1; //1进入试玩 为空进入真实游戏
        $aPost['wallet_type'] = 2; //当传1：转账钱包，当传2：免转钱包
        $sJson = $this->oCurl->post('/v1/user/login', $aPost);
        $ret = json_decode($sJson, TRUE);
        if (!empty($ret['statusCode']) && $ret['statusCode'] !== '01') {
            throw new \Exception($ret['message'], $ret['statusCode']);
        }
        return($ret['data']);
    }

    public function transScore($username, $plat_type, $money, $client_transfer_id) {
        $aPost = array();
        $aPost['sign_key'] = $this->aConfig['sign_key'];
        $code_text = $this->aConfig['sign_key'] . $this->aConfig['api_account'] . $username;
        $code_text .= $plat_type . $money . $client_transfer_id;
        $aPost['code'] = md5($code_text);
        $aPost['username'] = $username;
        $aPost['plat_type'] = $plat_type;
        $aPost['money'] = $money;
        $aPost['client_transfer_id'] = $client_transfer_id;
        $sJson = $this->oCurl->post('/v1/user/trans', $aPost);
        $ret = json_decode($sJson, TRUE);
        return $ret;
    }

    public function transAll($username) {
        $aPost = array();
        $aPost['sign_key'] = $this->aConfig['sign_key'];
        $aPost['code'] = md5($this->aConfig['sign_key'] . $this->aConfig['api_account'] . $username);
        $aPost['username'] = $username;
        $sJson = $this->oCurl->post('/v1/user/trans-all', $aPost);
        $ret = json_decode($sJson, TRUE);
        return $ret;
        if (!empty($ret['statusCode']) && $ret['statusCode'] !== '01') {
            throw new \Exception($ret['message'], $ret['statusCode']);
        }
        return($ret['data']);
    }

}
