<?php

namespace FiradioPHP\Api;

use FiradioPHP\Socket\Curl;

class IKuai3 {

    private $aConfig;
    private $oCurl;
    private $oDb;

    public function __construct($conf) {
        $this->aConfig = $conf;
        $this->oCurl = new Curl($conf['URL']);
    }

    public function setDb($oDb) {
        $this->oDb = $oDb;
    }

    private function getList($func_name, $param_type) {
        $this->oCurl->setHeader('Content-Type', 'application/json');
        $aPost = array();
        $aPost['func_name'] = $func_name;
        $aPost['action'] = 'show';
        $aPost['param'] = array('TYPE' => $param_type);
        //$this->oCurl->setTimeout(60);
        $sJson = $this->oCurl->post('/Action/call', $aPost);
        //$this->oCurl->setTimeout(10);
        $aJson = json_decode($sJson, true);
        if (!is_array($aJson)) {
            $this->error('getList-Error: ' . $sJson);
        }
        if ($aJson['Result'] == '10014' || $aJson['ErrMsg'] == 'no login authentication') {
            $this->error('[ErrMsg] => no login authentication');
        }
        return $aJson;
    }

    private function error($message, $title = '提示') {
        $ex = new \Exception($message, -2);
        $ex->title = $title;
        throw $ex;
    }

    private function data_exist($rows, $data) {
        foreach ($rows as $row) {
            if ($row['wan_port'] == $data['wan_port']) {
                if ($row['wan_addr'] == $data['wan_addr']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function login() {
        $post = array();
        $post['username'] = $this->aConfig['username'];
        $post['passwd'] = md5($this->aConfig['password']);
        $post['pass'] = base64_encode('salt_11' . $this->aConfig['password']);
        $sJson = $this->oCurl->post('/Action/login', $post);
        $aJson = json_decode($sJson, true);
        print_r($aJson);
    }

    public function uploadPortmap($file) {
        $mimeType = 'application/vnd.ms-excel';
        $post = array();
        $post['dnat.csv'] = curl_file_create($file, $mimeType, basename($file));
        $this->oCurl->upload(TRUE);
        $jsonStr = $this->oCurl->post('/Action/upload', $post);
        $this->oCurl->upload(FALSE);
        $jsonArr = json_decode($jsonStr, TRUE);
        print_r($jsonArr);
        $aPost = array();
        $aPost['func_name'] = 'dnat';
        $aPost['action'] = 'IMPORT';
        $aPost['param'] = array();
        $aPost['param']['filename'] = 'dnat.csv';
        $aPost['param']['append'] = 0;
        $this->oCurl->setHeader('Content-Type', 'application/json');
        $this->oCurl->setTimeout(60);
        $sJson = $this->oCurl->post('/Action/call', $aPost);
        $this->oCurl->setTimeout();
        $aJson = json_decode($sJson, true);
        if (!is_array($aJson)) {
            $this->error('getList-Error: ' . $sJson);
        }
        print_r($aJson);
    }

    private function pppoe_getUpTime($updatetime) {
        return time() - intval($updatetime);
    }

    private function pppoe_getRow($row) {
        return $row;
    }

    public function pppoe_getList() {
        $aJsonData = $this->getList('monitor_iface', 'iface_check,iface_stream');
        $aIfaceCheck = $aJsonData['Data']['iface_check'];
        $aReturn = array();
        foreach ($aIfaceCheck as $row) {
            if (strpos($row['interface'], 'adsl') === 0) {
                $row['upseconds'] = $this->pppoe_getUpTime($row['updatetime']);
                $aReturn[$row['interface']] = $this->pppoe_getRow($row);
            }
        }
        return $aReturn;
    }

}
