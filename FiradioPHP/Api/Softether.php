<?php

namespace FiradioPHP\Api;

use FiradioPHP\F;
use FiradioPHP\Socket\Curl;

class Softether {

    private $aConfig;
    private $oCurl;
    private $aCache = array();

    public function __construct($conf) {
        $this->aConfig = $conf['config'];
        $url = 'https://' . $this->aConfig['host'] . ':' . $this->aConfig['port'] . $this->aConfig['path'];
        echo $url . "\r\n";
        $this->oCurl = new Curl($url);
        //$this->oCurl->setHeader('Content-Type', 'application/json');
        $this->oCurl->setHeader('X-VPNADMIN-HUBNAME', $this->aConfig['hubname']);
        $this->oCurl->setHeader('X-VPNADMIN-PASSWORD', $this->aConfig['password']);
    }

    private function api($method, $params = array()) {
        $aPost = array();
        $aPost['jsonrpc'] = '2.0';
        $aPost['id'] = 'rpc_call_id';
        $aPost['method'] = $method;
        $aPost['params'] = $params;
        $sJson = $this->oCurl->post_json('', $aPost);
        //print_r($this->oCurl->response_header);
        $aJson = json_decode($sJson, true);
        return $aJson;
    }

    private function error($message, $title = '提示') {
        $ex = new \Exception($message, -2);
        $ex->title = $title;
        throw $ex;
    }

    public function Test() {
        $params = array();
        $params['IntValue_u32'] = 0;
        return $this->api('Test', $params);
    }

    public function GetUser($Name_str, $HubName_str = 'DEFAULT') {
        $params = array();
        $params['HubName_str'] = $HubName_str;
        $params['Name_str'] = $Name_str;
        return $this->api('GetUser', $params);
    }

}
