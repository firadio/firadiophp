<?php

namespace FiradioPHP\Api;

use FiradioPHP\F;
use FiradioPHP\Socket\Curl;

class Netease {

    private $aConfig;
    public $oCurl;

    public function __construct($conf = array()) {
        $this->aConfig = $conf;
        $sUrl = 'https://vcloud.163.com/app';
        $this->oCurl = new Curl($sUrl);
        $this->oCurl->setHeader('Content-Type', 'application/json;charset=UTF-8');
        $this->setAppKey($conf['app_key']);
        $this->setAppSecret($conf['app_secret']);
    }

    public function setAppKey($sAppKey) {
        $this->oCurl->setHeader('AppKey', $sAppKey);
    }

    public function setAppSecret($sAppSecret) {
        $sNonce = mt_rand();
        $sCurTime = time();
        $sCheckSum = $this->getCheckSum($sAppSecret, $sNonce, $sCurTime);
        $this->oCurl->setHeader('Nonce', $sNonce);
        $this->oCurl->setHeader('CurTime', $sCurTime);
        $this->oCurl->setHeader('CheckSum', $sCheckSum);
    }

    public function getCheckSum($sAppSecret, $sNonce, $sCurTime) {
        return sha1($sAppSecret . $sNonce . $sCurTime);
    }

    private function post($sPath, $aPost) {
        $ret = $this->oCurl->post($sPath, $aPost);
        if ($this->oCurl->getHttpStatus() !== 200) {
            print_r($this->oCurl->response_header);
            F::error('return HttpStatus=' . $this->oCurl->getHttpStatus());
            return;
        }
        $ret = json_decode($ret, true);
        if (isset($ret['code']) && isset($ret['msg'])) {
            $this->error($ret['msg']);
        }
        return $ret['ret'];
    }

    public function channel_create($sName, $iType = 0) {
        //2.1 创建频道
        $aPost = array();
        $aPost['name'] = $sName;
        $aPost['type'] = $iType;
        $ret = $this->post('/channel/create', $aPost);
        $resual = array();
        $resual['pull_rtmp'] = $ret['rtmpPullUrl'];
        $resual['pull_http'] = $ret['httpPullUrl'];
        $resual['pull_hls'] = $ret['hlsPullUrl'];
        $resual['tp_cid'] = $ret['cid'];
        $resual['tp_name'] = $ret['name'];
        $resual['push_url'] = $ret['pushUrl'];
        return $resual;
    }

    public function channel_update($iCid, $sName, $iType = 0) {
        //2.2 修改频道
        $aPost = array();
        $aPost['cid'] = $iCid;
        $aPost['name'] = $sName;
        $aPost['type'] = $iType;
        $ret = $this->post('/channel/update', $aPost);
        return $ret;
    }

    public function channel_delete($iCid) {
        //2.3 删除频道
        $aPost = array();
        $aPost['cid'] = $iCid;
        $ret = $this->post('/channel/delete', $aPost);
        return $ret;
    }

    public function channel_stats($iCid) {
        //2.4 获取频道状态
        $aPost = array();
        $aPost['cid'] = $iCid;
        $ret = $this->post('/channelstats', $aPost);
        return $ret;
    }

    public function channel_list($iRecords = 10, $iPnum = 1, $sOfield = '', $iSort = 1) {
        //2.5 获取频道列表
        $aPost = array();
        $aPost['records'] = $iRecords;
        $aPost['pnum'] = $iPnum;
        $aPost['ofield'] = $sOfield;
        $aPost['sort'] = $iSort;
        $ret = $this->post('/channellist', $aPost);
        $retRows = array();
        foreach ($ret['list'] as $row) {
            $retRow = array();
            $retRow['tp_cid'] = $row['cid'];
            $retRow['tp_name'] = $row['name'];
            $retRows[] = $retRow;
        }
        return $retRows;
    }

    public function channel_address($iCid) {
        //2.6 重新获取推流地址
        $aPost = array();
        $aPost['cid'] = $iCid;
        $ret = $this->post('/address', $aPost);
        return $ret;
    }

    private function error($message, $title = 'Netease提示') {
        $ex = new \Exception($message, -2);
        $ex->title = $title;
        throw $ex;
    }

}
