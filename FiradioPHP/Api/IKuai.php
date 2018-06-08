<?php

namespace FiradioPHP\Api;

use FiradioPHP\F;
use FiradioPHP\Socket\Curl;

class IKuai {

    private $aConfig;
    private $aApi = array();
    private $oCurl;
    private $oDb;

    public function __construct($conf) {
        $this->aConfig = $conf;
        $this->oCurl = new Curl($conf['URL']);
        $this->oCurl->setHeader('Cookie', 'PHPSESSID=' . $conf['PHPSESSID'] . '; perpage=10000');
    }

    public function setDb($oDb) {
        $this->oDb = $oDb;
    }

    private function getData($path) {
        $sJson = $this->oCurl->get('/index.php' . $path);
        $aJson = json_decode($sJson, true);
        if (!isset($aJson['data'])) {
            $this->error('没有找到aJson的索引data');
        }
        return $aJson['data'];
    }

    private function getList($path) {
        $sJson = $this->oCurl->get('/index.php' . $path);
        $aJson = json_decode($sJson, true);
        if (!is_array($aJson)) {
            $this->error('is not a array');
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
        $post['user'] = $this->aConfig['username'];
        $post['pass'] = $this->aConfig['password'];
        $sJson = $this->oCurl->post('/login/x', $post);
        $aJson = json_decode($sJson, true);
        print_r($aJson);
    }

    public function updatePortmap() {
        $aJsonData = $this->getData('/Service/nat/page_data');
        $field = 'id,state,comment,wan_addr,lan_addr,protocol,wan_port,lan_port';
        $rows = $this->oDb->sql()->table('ikuai_portmap')->field($field)->select();
        foreach ($aJsonData as $data) {
            if (!is_numeric($data['wan_port'])) {
                continue;
            }
            if (!is_numeric($data['lan_port'])) {
                continue;
            }
            if ($this->data_exist($rows, $data)) {
                continue;
            }
            $this->oDb->sql()->table('ikuai_portmap')->add($data);
        }
        $this->oDb->commit();
    }

    private function pppoe_getUpTime($updatetime) {
        $updatetime = str_replace('/', ' ', $updatetime);
        return time() - strtotime($updatetime);
    }

    private function pppoe_getRow($row) {
        return $row;
    }

    public function pppoe_getList() {
        $aJsonData = $this->getList('/System/monitoring_lines/refresh');
        $aReturn = array();
        foreach ($aJsonData as $row) {
            if (strpos($row['interface'], 'adsl') === 0) {
                $row['upseconds'] = $this->pppoe_getUpTime($row['updatetime']);
                $aReturn[$row['interface']] = $this->pppoe_getRow($row);
            }
        }
        return $aReturn;
    }

}
