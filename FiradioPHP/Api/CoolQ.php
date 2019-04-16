<?php

namespace FiradioPHP\Api;

use FiradioPHP\Socket\Curl;
use FiradioPHP\Socket\HttpClient;

/**
 * https://github.com/richardchien/coolq-http-api
 * https://richardchien.github.io/coolq-http-api
 */
class CoolQ {

    private $aConfig;
    private $oCurl;
    public $oHttpClient;

    public function __construct($conf = array()) {
        $this->aConfig = $conf;
    }

    public function setConfig($sHost, $iPort = 80, $sToken = '') {
        $sUrl = 'http://' . $sHost . ':' . $iPort;
        $this->oCurl = new Curl($sUrl);
        $this->oCurl->setHeader('Content-Type', 'application/x-www-form-urlencoded');
        $this->oCurl->setHeader('Authorization', 'token ' . $sToken);
        $this->oHttpClient = new HttpClient();
        $this->oHttpClient->setHostPort($sHost, $iPort);
        $this->oHttpClient->setHeader('Authorization', 'token ' . $sToken);
    }

    private function oHttpClient() {
        return $this->oCurl;
    }

    private function getData($path, $post) {
        $ret = $this->oHttpClient()->post_json($path, $post);
        $ret = json_decode($ret, true);
        if (!is_array($ret)) {
            return array('message' => 'is not a array');
        }
        if (!isset($ret['data'])) {
            return $ret;
        }
        return $ret['data'];
    }

    /**
     * 发送私聊消息
     * @param int $qquin
     * @param string $message
     * @param bool $is_raw
     */
    public function send_private_msg($qquin, $message, $auto_escape = false) {
        $post = array();
        $post['user_id'] = $qquin;
        $post['message'] = $message;
        $post['auto_escape'] = $auto_escape;
        $data = $this->getData('/send_private_msg', $post);
        return $data;
    }

    public function send_group_msg($group_id, $message, $auto_escape = false) {
        $post = array();
        $post['group_id'] = $group_id;
        $post['message'] = $message;
        $post['auto_escape'] = $auto_escape;
        $data = $this->getData('/send_group_msg', $post);
        return $data;
    }

    public function set_friend_add_request($flag, $approve = true, $remark = NULL, $fCallBack = NULL) {
        $post = array();
        $post['flag'] = $flag;
        $post['approve'] = $approve;
        $post['remark'] = $remark;
        $ret = $this->oHttpClient()->post('/set_friend_add_request', $post);
        //$ret = $this->oHttpClient()->post_json('/set_friend_add_request', $post, $fCallBack);
    }

    public function get_stranger_info($user_id, $no_cache = FALSE) {
        $post = array();
        $post['user_id'] = $user_id;
        $post['no_cache'] = $no_cache;
        $ret = $this->oHttpClient()->post('/get_stranger_info', $post);
        $ret = json_decode($ret, true);
        return $ret;
    }

    public function delete_msg($message_id) {
        $post = array();
        $post['message_id'] = $message_id;
        $data = $this->getData('/delete_msg', $post);
        return $data;
    }

    public function set_group_ban($group_id, $user_id, $duration) {
        $post = array();
        $post['group_id'] = $group_id;
        $post['user_id'] = $user_id;
        $post['duration'] = $duration;
        $data = $this->getData('/set_group_ban', $post);
        return $data;
    }

}
