<?php

namespace FiradioPHP\Api;

use FiradioPHP\F;
use FiradioPHP\Socket\Curl;
use FiradioPHP\Socket\HttpClient;

class Live {

    private $aConfig;
    private $aApi = array();
    private $oDb;

    public function __construct($conf) {
        $this->aConfig = $conf;
    }

    public function setDb($oDb) {
        $this->oDb = $oDb;
    }

    public function getApi($class, $config) {
        $key = $class . '_' . $config['app_key'];
        if (!isset($this->aApi[$key])) {
            if (!class_exists($class)) {
                $this->error('The class does not exist in the ' . $class);
            }
            $this->aApi[$key] = new $class($config);
        }
        return $this->aApi[$key];
    }

    public function getNewApiChannel($member_uid) {
        //获取一个新的API频道
        $sql_table = '{tablepre}api_app aa LEFT JOIN {tablepre}api_class ac ON ac.id=aa.class_id';
        $sql_field = 'aa.id,aa.app_key,aa.app_secret,ac.php_class';
        $sql = 'SELECT ' . $sql_field . ' FROM ' . $sql_table . ' WHERE NOT ISNULL(aa.enabled)';
        $sql .= ' AND ISNULL(aa.deleted)';
        $row_aa = $this->oDb->fetchOne($sql); //取得一个API
        $class = $row_aa['php_class'];
        $oApi = $this->getApi($class, $row_aa);
        $ret = $oApi->channel_create('uid-' . $member_uid);
        if (!isset($ret['tp_cid'])) {
            $this->error('频道创建失败,请与客服联系 at getNewApiChannel()');
        }
        $row_ac = $this->get_push_info($ret['push_url']);
        $row_ac['create_uid'] = $member_uid;
        $row_ac['api_app_id'] = $row_aa['id'];
        $row_ac['pull_rtmp'] = $ret['pull_rtmp'];
        $row_ac['pull_http'] = $ret['pull_http'];
        $row_ac['pull_hls'] = $ret['pull_hls'];
        $row_ac['tp_cid'] = $ret['tp_cid'];
        $row_ac['tp_name'] = $ret['tp_name'];
        $row_ac['id'] = $this->oDb->insert('api_channel', $row_ac);
        $this->oDb->commit();
        $this->oDb->beginTransaction();
        return $row_ac;
    }

    public function getApiChannelByFromId($id) {
        //获取一个新的API频道
        $sql_table = '{tablepre}api_channel ac';
        $sql_table .= ' LEFT JOIN {tablepre}nj nj ON nj.create_uid=ac.from_uid';
        $sql_field = 'ac.id,ac.from_uid,ac.pull_rtmp,ac.pull_http,ac.pull_hls';
        $sql_field .= ',nj.username,nj.nickname,nj.qquin,nj.wechat';
        $sql_field .= ',nj.zhibo_title,nj.zhibo_summary,nj.zhibo_content';
        $sql_where = array('ac.id' => $id, 'ac.status' => 1, 'ac.deleted' => NULL);
        $row_ac = $this->oDb->sql()->table($sql_table)->field($sql_field)->where($sql_where)->desc('ac.updated')->find();
        return $row_ac;
    }

    public function getApiChannelByFromUid($member_uid) {
        //获取一个新的API频道
        $sql_table = '{tablepre}api_channel ac';
        $sql_table .= ' LEFT JOIN {tablepre}nj nj ON nj.create_uid=ac.from_uid';
        $sql_field = 'ac.id,ac.from_uid,ac.pull_rtmp,ac.pull_http,ac.pull_hls';
        $sql_field .= ',nj.username,nj.nickname,nj.qquin,nj.wechat';
        $sql_field .= ',nj.zhibo_title,nj.zhibo_summary,nj.zhibo_content';
        $sql_where = array('ac.from_uid' => $member_uid, 'ac.status' => 1, 'ac.deleted' => NULL);
        $row_ac = $this->oDb->sql()->table($sql_table)->field($sql_field)->where($sql_where)->desc('ac.updated')->find();
        return $row_ac;
    }

    public function getApiChannelByFromUid_bak($member_uid) {
        //获取一个新的API频道
        $sql_table = '{tablepre}api_channel ac';
        $sql_table .= ' LEFT JOIN {tablepre}api_app aa ON aa.id=ac.api_app_id';
        $sql_field = 'ac.id,ac.pull_rtmp,ac.pull_http,ac.pull_hls';
        $sql_where = 'ac.from_uid=:from_uid AND ac.status=:status AND ISNULL(ac.deleted) AND NOT ISNULL(aa.enabled)';
        $sql_param = array('from_uid' => $member_uid, 'status' => 1);
        $row_ac = $this->oDb->sql()->table($sql_table)->field($sql_field)->where($sql_where)->param($sql_param)->desc('ac.updated')->find();
        return $row_ac;
    }

    public function api_channel_update($oApi, $api_app_id, $tp_cid) {
        $this->oDb->beginTransaction();
        $sql_field = 'id,tp_cid,tp_name';
        // $sql_field.= ',push_host,push_key';
        $sql = 'SELECT ' . $sql_field . ' FROM {tablepre}api_channel WHERE api_app_id=:api_app_id AND tp_cid=:tp_cid';
        $row = $this->oDb->fetchOne($sql, array('api_app_id' => $api_app_id, 'tp_cid' => $tp_cid));
        if (empty($row)) {
            $ret = $oApi->channel_address($tp_cid);
            if (!isset($ret['name']) && !isset($ret['push_url'])) {
                $this->error('API获取推流地址时数据错误');
            }
            $row = $this->get_push_info($ret['push_url']);
            $row['api_app_id'] = $api_app_id;
            $row['tp_cid'] = $tp_cid;
            $row['tp_name'] = $ret['name'];
            $row['pull_rtmp'] = $ret['pull_rtmp'];
            $row['pull_http'] = $ret['pull_http'];
            $row['pull_hls'] = $ret['pull_hls'];
            $insert_id = $this->oDb->insert('api_channel', $row);
        }
        $this->oDb->commit();
        return $row;
    }

    private function get_push_info($pushUrl) {
        $row = array();
        $url = parse_url($pushUrl);
        $row['push_scheme'] = $url['scheme'];
        $row['push_host'] = $url['host'] . (isset($url['port']) ? (':' . $url['port']) : '');
        $path = explode('/', $url['path']);
        $row['push_path'] = '/' . $path[1];
        $row['push_key'] = $path[2];
        if (isset($url['query'])) {
            $row['push_key'] .= '?' . $url['query'];
        }
        return $row;
    }

    private function error($message, $title = '提示') {
        $ex = new \Exception($message, -2);
        $ex->title = $title;
        throw $ex;
    }

}
