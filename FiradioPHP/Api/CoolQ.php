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
    private $msglimit = array();

    public function __construct($conf) {
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

    /**
     * 发送私聊消息
     * @param int $qquin
     * @param string $message
     * @param bool $is_raw
     */
    public function send_private_msg($qquin, $message, $is_raw = false) {
        $msglimit_str = $message;
        $msgtime = isset($this->msglimit[$msglimit_str]) ? $this->msglimit[$msglimit_str] : 0;
        if (time() - $msgtime < 60) {
            //相同的$message间隔时间不能小于60秒
            return;
        }
        $this->msglimit[$msglimit_str] = time();
        $post = array();
        $post['user_id'] = $qquin;
        $post['message'] = $message;
        $post['is_raw'] = $is_raw;
        $ret = $this->oHttpClient->post_json('/send_private_msg', $post);
    }

    public function send_group_msg($group_id, $message, $is_raw = false) {
        $msglimit_str = $message;
        $msgtime = isset($this->msglimit[$msglimit_str]) ? $this->msglimit[$msglimit_str] : 0;
        if (time() - $msgtime < 60) {
            //相同的$message间隔时间不能小于60秒
            return;
        }
        $this->msglimit[$msglimit_str] = time();
        $post = array();
        $post['group_id'] = $group_id;
        $post['message'] = $message;
        $post['is_raw'] = $is_raw;
        $ret = $this->oHttpClient->post_json('/send_group_msg', $post);
    }

    public function set_friend_add_request($flag, $approve = true, $remark = NULL, $fCallBack = NULL) {
        $post = array();
        $post['flag'] = $flag;
        $post['approve'] = $approve;
        $post['remark'] = $remark;
        $ret = $this->oCurl->post('/set_friend_add_request', $post);
        //$ret = $this->oHttpClient->post_json('/set_friend_add_request', $post, $fCallBack);
    }

    public function get_stranger_info($user_id, $no_cache = FALSE) {
        $post = array();
        $post['user_id'] = $user_id;
        $post['no_cache'] = $no_cache;
        $ret = $this->oCurl->post('/get_stranger_info', $post);
        $ret = json_decode($ret, true);
        return $ret;
    }

}
