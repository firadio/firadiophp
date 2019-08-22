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

    public function setCookiFileLocation($file) {
        $this->oCurl->setCookiFileLocation($file);
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

    public function downloadPortmap($file = NULL) {
        $aPost = array();
        $aPost['func_name'] = 'dnat';
        $aPost['action'] = 'EXPORT';
        $aPost['param'] = array();
        $aPost['param']['format'] = 'csv';
        $this->oCurl->setHeader('Content-Type', 'application/json');
        $sJson = $this->oCurl->post('/Action/call', $aPost);
        $aJson = json_decode($sJson, true);
        if (!is_array($aJson)) {
            $this->error('getList-Error: ' . $sJson);
        }
        if ($aJson['Result'] == '10014' || $aJson['ErrMsg'] == 'no login authentication') {
            echo 'start login authentication....';
            $this->login();
            sleep(1);
            return $this->downloadPortmap($file);
            //return $data;
            // $this->error('[ErrMsg] => no login authentication');
        }
        if (isset($aJson['Filename'])) {
            $data = $this->oCurl->get('/Action/download', array('filename' => $aJson['Filename']));
            $data = iconv('gbk', 'utf-8', $data);
            if ($file) {
                file_put_contents($file, $data);
            }
            return $data;
        }
    }

    public function delPortmap($ids) {
        //{"func_name":"dnat","action":"del","param":{"id":"1094,1096,1097"}}
        $aPost = array();
        $aPost['func_name'] = 'dnat';
        $aPost['action'] = 'del';
        $aPost['param'] = array();
        $aPost['param']['id'] = implode(',', $ids);
        $this->oCurl->setHeader('Content-Type', 'application/json');
        $sJson = $this->oCurl->post('/Action/call', $aPost);
        $aJson = json_decode($sJson, true);
        if (!is_array($aJson)) {
            $this->error('getList-Error: ' . $sJson);
        }
    }

    public function addPortmap($param) {
        //{"func_name":"dnat","action":"add","param":{"enabled":"yes","comment":"123123","interface":"adsl1","lan_addr":"1.1.1.1","protocol":"tcp","wan_port":"65501","lan_port":"1111"}}
        //{"func_name":"dnat","action":"add","param":{"enabled":"yes","comment":"","interface":"all","lan_addr":"12.2.5.1","protocol":"tcp+udp","wan_port":"49","lan_port":"123"}}
        $aPost = array();
        $aPost['func_name'] = 'dnat';
        $aPost['action'] = 'add';
        $aPost['param'] = $param;
        $this->oCurl->setHeader('Content-Type', 'application/json');
        $sJson = $this->oCurl->post('/Action/call', $aPost);
        $aJson = json_decode($sJson, true);
        if (!is_array($aJson)) {
            $this->error('getList-Error: ' . $sJson);
        }
        if ($aJson['Result'] == '10014' || $aJson['ErrMsg'] == 'no login authentication') {
            // $this->error('[ErrMsg] => no login authentication');
            $this->login();
        }
        return $aJson;
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
            if ($row['internet'] === 'PPPOE') {
                $row['upseconds'] = $this->pppoe_getUpTime($row['updatetime']);
                $aReturn[$row['interface']] = $this->pppoe_getRow($row);
            }
        }
        return $aReturn;
    }

    public function arrayToHashtable($rows, $field) {
        $htRet = array();
        foreach ($rows as $row) {
            if (!array_key_exists($field, $row)) {
                continue;
            }
            $htRet[$row[$field]] = $row;
        }
        return $htRet;
    }

    private function is_ipv4($ip) {
        $ret = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE);
        return $ret !== FALSE;
        $pattern = '/^(?:(?:2[0-4][0-9]\.)|(?:25[0-5]\.)|(?:1[0-9][0-9]\.)|(?:[1-9][0-9]\.)|(?:[0-9]\.)){3}(?:(?:2[0-5][0-5])|(?:25[0-5])|(?:1[0-9][0-9])|(?:[1-9][0-9])|(?:[0-9]))$/';
        $ret = preg_match($pattern, $ip);
        return $ret;
    }

    public function pppoe_getOne() {
        $aJsonData = $this->getList('monitor_iface', 'iface_check,iface_stream');
        $aIfaceCheck = $this->arrayToHashtable($aJsonData['Data']['iface_check'], 'interface');
        $aIfaceStream = $this->arrayToHashtable($aJsonData['Data']['iface_stream'], 'interface');
        $aIfaceAll = array_replace_recursive($aIfaceCheck, $aIfaceStream);
        $aReturn = array();
        $minconn_row = array();
        foreach ($aIfaceAll as $row) {
            if (!array_key_exists('internet', $row)) {
                continue;
            }
            if ($row['interface'] === 'wan1') {
                continue;
            }
            if (empty($row['ip_addr'])) {
                continue;
            }
            if (!$this->is_ipv4($row['ip_addr'])) {
                continue;
            }
            if ($row['internet'] === 'PPPOE') {
                // print_r($row);
                $num_field = 'connect_num';
                $row[$num_field] = intval($row[$num_field]);
                if (empty($minconn_row)
                    || $minconn_row[$num_field] == 0
                    || $row[$num_field] < $minconn_row[$num_field]
                        // && $row[$num_field] > 0
                        // && $row['upload'] > 0 && $row['download'] > 0
                    ) {
                        $minconn_row = $row;
                }
            }
        }
        // print_r($minconn_row);
        return $minconn_row;
    }

    public function ping($interface, $host, $count = 1) {
        $aPost = array();
        $aPost['func_name'] = 'Ping';
        $aPost['action'] = 'start';
        $param = array();
        $param['interface'] = $interface;
        $param['host'] = $host;
        $param['count'] = $count;
        $aPost['param'] = $param;
        $this->oCurl->setHeader('Content-Type', 'application/json');
        $sJson = $this->oCurl->post('/Action/call', $aPost);
        $aJson = json_decode($sJson, true);
        if (!is_array($aJson)) {
            $this->error('getList-Error: ' . $sJson);
        }
        sleep(1);
        $aPost['action'] = 'show';
        $this->oCurl->setHeader('Content-Type', 'application/json');
        $sJson = $this->oCurl->post('/Action/call', $aPost);
        $aJson = json_decode($sJson, true);
        if (empty($aJson['Data'])) {
            return false;
        }
        if (empty($aJson['Data']['data'])) {
            return false;
        }
        if (empty($aJson['Data']['data'][0])) {
            return false;
        }
        if (empty($aJson['Data']['data'][0]['response'])) {
            return false;
        }
        $response = explode("\n", $aJson['Data']['data'][0]['response']);
        $response = explode(' ', $response[1]);
        $ret = array();
        $ret['ip'] = explode(':', $response[3])[0];
        $ret['ttl'] = explode('=', $response[5])[1];
        $ret['time'] = explode('=', $response[6])[1];
        return $ret;
    }

    public function acl_edit($param = array()) {
        $param['action'] = isset($param['action']) ? $param['action'] : 'drop';
        $param['comment'] = isset($param['comment']) ? $param['comment'] : '';
        $param['dir'] = isset($param['dir']) ? $param['dir'] : 'forward';
        $param['dst_addr'] = isset($param['dst_addr']) ? $param['dst_addr'] : '';
        $param['dst_port'] = isset($param['dst_port']) ? $param['dst_port'] : '';
        $param['enabled'] = isset($param['enabled']) ? $param['enabled'] : 'yes';
        $param['id'] = isset($param['id']) ? $param['id'] : '';
        $param['iinterface'] = isset($param['iinterface']) ? $param['iinterface'] : 'any';
        $param['ointerface'] = isset($param['ointerface']) ? $param['ointerface'] : 'any';
        $param['protocol'] = isset($param['protocol']) ? $param['protocol'] : 'any';
        $param['src_addr'] = isset($param['src_addr']) ? $param['src_addr'] : '';
        $param['src_port'] = isset($param['src_port']) ? $param['src_port'] : '';
        $param['time'] = isset($param['time']) ? $param['time'] : '00:00-23:59';
        $param['week'] = isset($param['week']) ? $param['week'] : '1234567';
        $aPost = array();
        $aPost['func_name'] = 'acl';
        $aPost['action'] = 'edit';
        $aPost['param'] = $param;
        $this->oCurl->setHeader('Content-Type', 'application/json');
        $sJson = $this->oCurl->post('/Action/call', $aPost);
        $aJson = json_decode($sJson, true);
        if (!is_array($aJson)) {
            $this->error('getList-Error: ' . $sJson);
        }
        return $aJson;
    }
}
