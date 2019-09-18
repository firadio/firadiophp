<?php

namespace FiradioPHP\Api;

use FiradioPHP\F;
use FiradioPHP\Socket\Curl;

class Softether {

    private $aConfig;
    private $oCurl;
    private $aCache = array();

    public function __construct($conf) {
        $this->aConfig = $conf;
        $url = 'https://' . $this->aConfig['host'] . ':' . $this->aConfig['port'] . $this->aConfig['path'];
        echo $url . "\r\n";
        $this->oCurl = new Curl($url);
        //$this->oCurl->setHeader('Content-Type', 'application/json');
        $this->oCurl->setHeader('X-VPNADMIN-HUBNAME', $this->aConfig['hubname']);
        $this->oCurl->setHeader('X-VPNADMIN-PASSWORD', $this->aConfig['password']);
        $this->aCache['HubName_str'] = 'DEFAULT';
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

    public function SetHubName($HubName_str) {
        $this->aCache['HubName_str'] = $HubName_str;
    }

    public function GetDefaParams($HubName_str = NULL) {
        $params = array();
        $params['HubName_str'] = ($HubName_str === NULL) ? $this->aCache['HubName_str'] : $HubName_str;
        return $params;
    }

    public function CreateHub($HubName_str = NULL) {
        $params = $this->GetDefaParams($HubName_str);
        $params['Online_bool'] = true;
        $params['NoEnum_bool'] = true;
        return $this->api('CreateHub', $params);
    }

    public function SetHubLog($HubName_str = NULL) {
        $params = $this->GetDefaParams($HubName_str);
        $params['SaveSecurityLog_bool'] = false;
        $params['SavePacketLog_bool'] = false;
        return $this->api('SetHubLog', $params);
    }

    public function CreateGroup($Name_str) {
        $params = $this->GetDefaParams();
        $params['Name_str'] = $Name_str;
        return $this->api('CreateGroup', $params);
    }

    public function SetGroup($Name_str) {
        $MultiLogins_u32 = 10;
        $params = $this->GetDefaParams();
        $params['Name_str'] = $Name_str;
        $params['UsePolicy_bool'] = true;
        $params['policy:Access_bool'] = true;
        $params['policy:ArpDhcpOnly_bool'] = true;
        $params['policy:CheckIP_bool'] = true;
        $params['policy:CheckIPv6_bool'] = true;
        $params['policy:CheckMac_bool'] = true;
        $params['policy:DHCPNoServer_bool'] = true;
        $params['policy:DHCPv6NoServer_bool'] = true;
        $params['policy:DHCPForce_bool'] = true;
        $params['policy:FilterNonIP_bool'] = true;
        $params['policy:MaxMac_u32'] = $MultiLogins_u32;
        $params['policy:MaxIP_u32'] = $MultiLogins_u32;
        $params['policy:MaxIPv6_u32'] = $MultiLogins_u32;
        $params['policy:MultiLogins_u32'] = $MultiLogins_u32;
        $params['policy:MaxUpload_u32'] = 10 * 1024 * 1024;
        $params['policy:MaxDownload_u32'] = 100 * 1024 * 1024;
        $params['policy:NoBridge_bool'] = true;
        $params['policy:NoRouting_bool'] = true;
        $params['policy:NoRoutingV6_bool'] = true;
        return $this->api('SetGroup', $params);
    }

    public function GetUser($Name_str, $HubName_str = NULL) {
        $params = $this->GetDefaParams($HubName_str);
        $params['Name_str'] = $Name_str;
        return $this->api('GetUser', $params);
    }

    public function CreateUser($Name_str, $Auth_Password_str, $GroupName_str = NULL, $dayLength = 0) {
        $params = $this->GetDefaParams();
        $params['Name_str'] = $Name_str;
        $params['AuthType_u32'] = 1;
        $params['Auth_Password_str'] = $Auth_Password_str;
        if ($GroupName_str) $params['GroupName_str'] = $GroupName_str;
        if ($dayLength) $params['ExpireTime_dt'] = gmdate('c', time() + $dayLength * 86400);
        return $this->api('CreateUser', $params);
    }

    public function SetUser($Name_str, $Auth_Password_str, $GroupName_str = NULL, $ExpireTime = 0) {
        $params = $this->GetDefaParams();
        $params['Name_str'] = $Name_str;
        $params['AuthType_u32'] = 1;
        $params['Auth_Password_str'] = $Auth_Password_str;
        if ($GroupName_str) $params['GroupName_str'] = $GroupName_str;
        if ($ExpireTime) $params['ExpireTime_dt'] = gmdate('c', $ExpireTime);
        return $this->api('SetUser', $params);
    }

}
