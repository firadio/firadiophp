<?php

namespace FiradioPHP\Api;

use FiradioPHP\F;
use FiradioPHP\Socket\Curl;

class Dnspod {

    private $aConfig;
    private $aApi = array();
    private $oCurl;
    private $oDb;
    private $aCache = array();

    public function __construct($conf) {
        $this->aConfig = $conf;
        $this->oCurl = new Curl($conf['URL']);
    }

    public function setDb($oDb) {
        $this->oDb = $oDb;
    }

    private function post($path, $aPost = array()) {
        $aPost['format'] = 'json';
        $aPost['login_token'] = $this->aConfig['token']['id'] . ',' . $this->aConfig['token']['key'];
        $sJson = $this->oCurl->post($path, $aPost);
        $aJson = json_decode($sJson, true);
        return $aJson;
    }

    private function error($message, $title = '提示') {
        $ex = new \Exception($message, -2);
        $ex->title = $title;
        throw $ex;
    }

    public function InfoVersion() {
        return $this->post('/Info.Version');
    }

    public function DomainList() {
        $data = $this->post('/Domain.List');
        $this->aCache['DomainList'] = $data;
        return $data;
    }

    public function getIdByDomain($param_domain) {
        if (!isset($this->aCache['DomainList'])) {
            $this->DomainList();
        }
        if (!isset($this->aCache['DomainList']['domains'])) {
            $this->error('not find domains in DomainList');
        }
        $domains = $this->aCache['DomainList']['domains'];
        foreach ($domains as $domain) {
            if ($domain['punycode'] === $param_domain) {
                return $domain['id'];
            }
        }
        return;
    }

    public function RecordList($domain, $sub_domain) {
        //$domain_id = $this->getIdByDomain($domain);
        $aPost = array();
        $aPost['domain'] = $domain;
        $aPost['sub_domain'] = $sub_domain;
        $data = $this->post('/Record.List', $aPost);
        $statusCode = $this->oCurl->getHttpStatus();
        //echo "\r\nstatusCode={$statusCode}";
        if ($statusCode != 200) {
            $this->error("\r\nstatusCode={$statusCode}");
        }
        return isset($data['records']) ? $data['records'] : array();
    }

    public function setRecordIPs($domain, $sub_domain, $aSets) {
        $records = $this->RecordList($domain, $sub_domain);
        foreach ($records as $record) {
            foreach ($aSets as $aSet) {
                if (strpos($record['remark'], $aSet['remark']) !== false) {
                    $aSet['name'] = $sub_domain;
                    $this->setRecordOne($domain, $record, $aSet);
                }
            }
        }
    }

    private function getStatusId($str) {
        if ($str === 'enable') {
            return 1;
        }
        if ($str === 'enabled') {
            return 1;
        }
        return 0;
    }

    private function RecordIsMatch($aOldSet, $aNewSet) {
        if ($aOldSet['name'] != $aNewSet['name']) {
            return false;
        }
        if ($aOldSet['type'] != $aNewSet['type']) {
            return false;
        }
        if ($aOldSet['value'] != $aNewSet['value']) {
            return false;
        }
        return true;
    }

    public function setRecordOne($domain, $aOldSet, $aNewSet) {
        if (0 && $this->getStatusId($aOldSet['status']) != $this->getStatusId($aNewSet['status'])) {
            $this->RecordStatus($domain, $aOldSet['id'], $aNewSet['status']);
        }
        if (!$this->RecordIsMatch($aOldSet, $aNewSet)) {
            $this->RecordModify($domain, $aOldSet['id'], $aNewSet);
        }
    }

    public function RecordModify($domain, $record_id, $aNewSet) {
        $aPost = array();
        $aPost['domain'] = $domain;
        $aPost['record_id'] = $record_id;
        if (isset($aNewSet['name'])) $aPost['sub_domain'] = $aNewSet['name'];
        if (isset($aNewSet['sub_domain'])) $aPost['sub_domain'] = $aNewSet['sub_domain'];
        if (isset($aNewSet['type'])) $aPost['record_type'] = $aNewSet['type'];
        if (isset($aNewSet['record_type'])) $aPost['record_type'] = $aNewSet['record_type'];
        $aPost['record_line_id'] = 0;
        $aPost['value'] = $aNewSet['value'];
        if (isset($aNewSet['status'])) $aPost['status'] = $aNewSet['status'];
        $data = $this->post('/Record.Modify', $aPost);
        return $data['status']['code'];
    }

    public function RecordStatus($domain, $record_id, $status) {
        $aPost = array();
        $aPost['domain'] = $domain;
        $aPost['record_id'] = $record_id;
        $aPost['status'] = $status;
        $data = $this->post('/Record.Status', $aPost);
        print_r($data);
        return $data;
    }

    public function RecordCreate($domain, $aSet) {
        $aPost = $aSet;
        $aPost['domain'] = $domain;
        $aPost['record_line'] = '默认';
        $data = $this->post('/Record.Create', $aPost);
        return $data['status']['code'];
    }


}
