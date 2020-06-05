<?php

namespace FiradioPHP\Api;

use FiradioPHP\Socket\Curl;

class NgApi {

    private $aConfig;
    private $oCurl;
    private $oCurl2;
    private $lastRet = array();

    public function __construct($conf = array()) {
        $this->aConfig = $conf['config'];
        if (!empty($this->aConfig['api_url'])) {
            $this->oCurl = new Curl($this->aConfig['api_url']);
        }
        if (!empty($this->aConfig['api2_url'])) {
            $this->oCurl2 = new Curl($this->aConfig['api2_url']);
            $this->oCurl2->postFormat = 'json';
        }
    }

    private function error($message) {
        throw new \Exception($message, -1);
    }

    public function retDataByPathAndPost($sPath, $aPost) {
        $sJson = $this->oCurl->post($sPath, $aPost);
        $ret = json_decode($sJson, TRUE);
        if (is_array($ret)) {
            $this->lastRet = $ret;
        } else {
            throw new \Exception('Api1返回错误1', -1);
        }
        if (empty($ret)) {
            throw new \Exception('Api1返回错误2', -1);
        }
        if (!empty($ret['statusCode']) && $ret['statusCode'] !== '01') {
            throw new \Exception($ret['message'], $ret['statusCode']);
        }
        if (!isset($ret['data'])) {
            throw new \Exception('Api1返回错误3', -1);
        }
        return($ret['data']);
    }

    public function api2($service, $aPost) {
        $aParam = array();
        $aParam['service'] = 'ngapi.' . $service;
        $aPost['site_id'] = $this->aConfig['api2_siteid'];
        $aPost['site_key'] = $this->aConfig['api2_sitekey'];
        $this->oCurl2->setParam($aParam);
        $this->oCurl2->setPost($aPost);
        $sJson = $this->oCurl2->execCurl();
        $ret = json_decode($sJson, TRUE);
        if (is_array($ret)) {
            $this->lastRet = $ret;
        } else {
            throw new \Exception("Api2返回错误1:{$sJson}", -1);
        }
        if (empty($ret)) {
            throw new \Exception('Api2返回错误2', -1);
        }
        if (!empty($ret['msg'])) {
            throw new \Exception($ret['msg'], intval($ret['ret']));
        }
        if (empty($ret['data'])) {
            throw new \Exception('Api2返回错误3', -1);
        }
        return $ret['data'];
    }

    public function getGameCategory() {
        $aPost = array();
        $aPost['sign_key'] = $this->aConfig['sign_key'];
        $aPost['code'] = md5($this->aConfig['sign_key'] . $this->aConfig['api_account']);
        return $this->retDataByPathAndPost('/v1/game/category', $aPost);
    }

    public function getGameCode($plat_type, $category_id = NULL) {
        $aPost = array();
        $aPost['sign_key'] = $this->aConfig['sign_key'];
        $aPost['code'] = md5($this->aConfig['sign_key'] . $this->aConfig['api_account'] . $plat_type);
        $aPost['plat_type'] = $plat_type;
        if ($category_id !== NULL) {
            $aPost['category_id'] = $category_id;
        }
        return $this->retDataByPathAndPost('/v1/game/code', $aPost);
    }

    public function getUserLoginUrl($username, $plat_type, $game_type, $game_code, $is_mobile_url = 1) {
        $sPlatType = strtolower($plat_type);
        $aPost = array();
        $aPost['sign_key'] = $this->aConfig['sign_key'];
        $code_text = $this->aConfig['sign_key'] . $this->aConfig['api_account'];
        $code_text .= $username . $sPlatType . $is_mobile_url;
        $aPost['code'] = md5($code_text);
        $aPost['username'] = $username;
        $aPost['plat_type'] = $sPlatType;
        $aPost['game_type'] = $game_type;
        $aPost['game_code'] = $game_code;
        $aPost['is_mobile_url'] = $is_mobile_url; //是否手机登录 【1是】【0不是】
        //$aPost['demo'] = 1; //1进入试玩 为空进入真实游戏
        $aPost['wallet_type'] = 2; //当传1：转账钱包，当传2：免转钱包
        return $this->retDataByPathAndPost('/v1/user/login', $aPost);
    }

    public function getUserBalance($username, $sPlatType) {
        $aPost = array();
        $aPost['sign_key'] = $this->aConfig['sign_key'];
        $code_text = $this->aConfig['sign_key'] . $this->aConfig['api_account'];
        $code_text .= $username . $sPlatType;
        $aPost['code'] = md5($code_text);
        $aPost['username'] = $username;
        $aPost['plat_type'] = $sPlatType;
        return $this->retDataByPathAndPost('/v1/user/balance', $aPost);
    }

    public function getUserAllBalance($username) {
        $aPost = array();
        $aPost['sign_key'] = $this->aConfig['sign_key'];
        $code_text = $this->aConfig['sign_key'] . $this->aConfig['api_account'];
        $code_text .= $username;
        $aPost['code'] = md5($code_text);
        $aPost['username'] = $username;
        return $this->retDataByPathAndPost('/v1/user/all-balance', $aPost);
    }

    public function transScoreAndGetBalance($username, $plat_type, $money, $client_transfer_id) {
        $aPost = array();
        $aPost['sign_key'] = $this->aConfig['sign_key'];
        $code_text = $this->aConfig['sign_key'] . $this->aConfig['api_account'] . $username;
        $code_text .= $plat_type . $money . $client_transfer_id;
        $aPost['code'] = md5($code_text);
        $aPost['username'] = $username;
        $aPost['plat_type'] = $plat_type;
        $aPost['money'] = $money;
        $aPost['client_transfer_id'] = $client_transfer_id;
        return $this->retDataByPathAndPost('/v1/user/trans', $aPost);
    }

    public function transAll($username) {
        $aPost = array();
        $aPost['sign_key'] = $this->aConfig['sign_key'];
        $aPost['code'] = md5($this->aConfig['sign_key'] . $this->aConfig['api_account'] . $username);
        $aPost['username'] = $username;
        return $this->retDataByPathAndPost('/v1/user/trans-all', $aPost);
    }

    public function do_user_gscorelog_one($oDb, $client_transfer_id, $apiType = 'api1') {
        $oDb->begin();
        $aWhereUserGScorelog = array();
        $aWhereUserGScorelog['client_transfer_id'] = $client_transfer_id;
        //检查订单是否有效
        $oSql = $oDb->sql()->table('user_gscorelog');
        $oSql->field('status,username,plat_type,money,site_id,site_uid');
        $oSql->where($aWhereUserGScorelog);
        $oSql->lock();
        $aRowUserGScorelog = $oSql->find();
        if (empty($aRowUserGScorelog)) {
            $this->error('订单没有找到');
            return FALSE;
        }
        if (intval($aRowUserGScorelog['status']) !== 0) {
            //状态不正确
            $this->error('状态不正确，应该传入0');
            return FALSE;
        }
        $username = $aRowUserGScorelog['username'];
        $plat_type = $aRowUserGScorelog['plat_type'];
        $money = floatval($aRowUserGScorelog['money']);
        //转账金额(负数表示从平台转出，正数转入)，不支持小数
        if (ceil($money) !== $money) {
            //不支持小数
            $this->error("不支持小数,您输入的是[{$money}]");
            return FALSE;
        }
        //执行远程API操作，返回当前余额
        $user_gscore_balance = NULL;
        $aSaveSiteCreditlog = array();
        $ex = NULL;
        if (TRUE) {
            try {
                if ($apiType === 'api1') {
                    $user_gscore_balance = $this->transScoreAndGetBalance($username, $plat_type, $money, $client_transfer_id);
                } else if ($apiType === 'api2') {
                    $aPost = array();
                    $aPost['site_uid'] = $aRowUserGScorelog['site_uid'];
                    $aPost['plat_type'] = $plat_type;
                    $aPost['money'] = $money;
                    $aPost['client_transfer_id'] = $client_transfer_id;
                    $aData = $this->api2('score_trans_in', $aPost);
                    if (isset($aData['balance'])) {
                        $user_gscore_balance = $aData['balance'];
                    }
                } else {
                    $this->error("不支持的 apiType = [{$apiType}]");
                }
                if ($user_gscore_balance !== NULL) {
                    //把最终的余额写入到user_gscore表
                    $aWhereUserScore = array();
                    $aWhereUserScore['username'] = $username;
                    $aWhereUserScore['plat_type'] = $plat_type;
                    $aSaveUserScore = array();
                    $aSaveUserScore['site_id'] = $aRowUserGScorelog['site_id'];
                    $aSaveUserScore['site_uid'] = $aRowUserGScorelog['site_uid'];
                    $aSaveUserScore['balance'] = $user_gscore_balance;
                    $oDb->sql()->table('user_gscore')->where($aWhereUserScore)->addwnesave($aSaveUserScore);
                }
                $aSaveSiteCreditlog['status'] = 1;
                $aSaveSiteCreditlog['api_trans_balance'] = $user_gscore_balance;
            } catch (\Exception $e) {
                $ex = $e;
                $aSaveSiteCreditlog['status'] = 2;
            }
            $aSaveSiteCreditlog['api_trans_code'] = isset($this->lastRet['statusCode']) ? $this->lastRet['statusCode'] : '';
            $aSaveSiteCreditlog['api_trans_msg'] = isset($this->lastRet['message']) ? $this->lastRet['message'] : '';
        }
        $oSql->where($aWhereUserGScorelog)->save($aSaveSiteCreditlog);
        if ($ex !== NULL) {
            throw $ex;
        }
        return $user_gscore_balance;
    }

}
