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
            if (isset($ret['statusCode'])) {
                $this->lastRet['code'] = $ret['statusCode'];
            }
            if (isset($ret['message'])) {
                $this->lastRet['msg'] = $ret['message'];
            }
        } else {
            throw new \Exception('Api1返回错误1', -1);
        }
        if (empty($ret)) {
            throw new \Exception('Api1返回错误2', -1);
        }
        if (!empty($ret['statusCode']) && $ret['statusCode'] !== '01') {
            throw new \Exception($ret['message'], -1);
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

    private function CheckTransUserScoreStatus($username, $plat_type, $client_transfer_id) {
        $aPost = array();
        $aPost['sign_key'] = $this->aConfig['sign_key'];
        $code_text = $this->aConfig['sign_key'] . $this->aConfig['api_account'] . $username;
        $code_text .= $plat_type . $client_transfer_id;
        $aPost['code'] = md5($code_text);
        $aPost['username'] = $username;
        $aPost['plat_type'] = $plat_type;
        $aPost['client_transfer_id'] = $client_transfer_id;
        return $this->retDataByPathAndPost('/v1/user/status', $aPost);
    }

    public function transAll($username) {
        $aPost = array();
        $aPost['sign_key'] = $this->aConfig['sign_key'];
        $aPost['code'] = md5($this->aConfig['sign_key'] . $this->aConfig['api_account'] . $username);
        $aPost['username'] = $username;
        return $this->retDataByPathAndPost('/v1/user/trans-all', $aPost);
    }

    public function v1_user_record_all() {
        $aPost = array();
        $aPost['sign_key'] = $this->aConfig['sign_key'];
        $before_second = 0;
        $time_scope_end = time() - $before_second;
        $time_scope_begin = $time_scope_end - 86400;
        $aPost['startTime'] = date('Y-m-d H:i:s', $time_scope_begin);
        $aPost['endTime'] = date('Y-m-d H:i:s', $time_scope_end);
        $aPost['timeType'] = 0; //默认为空按照最后更新时间获取记录 为1时按照下注时间获取记录
        $aPost['page'] = 1;
        $aPost['limit'] = 2000;
        $code_text = $this->aConfig['sign_key'] . $this->aConfig['api_account'];
        $code_text .= $aPost['startTime'] . $aPost['endTime'];
        $aPost['code'] = md5($code_text);
        return $this->retDataByPathAndPost('/v1/user/record-all', $aPost);
    }

    public function do_user_gscore_order_one($oDb, $client_transfer_id, $apiType = 'api1') {
        $oDb->begin();
        $aWhereUserGScoreOrder = array();
        $aWhereUserGScoreOrder['client_transfer_id'] = $client_transfer_id;
        //检查订单是否有效
        $oSql = $oDb->sql()->table('user_gscore_order');
        //$oSql->field('status,username,plat_type,money,site_id,site_uid');
        $oSql->where($aWhereUserGScoreOrder);
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
        $fUserScore = floatval($aRowUserGScorelog['money']);
        //转账金额(负数表示从平台转出，正数转入)，不支持小数
        if ($fUserScore === 0) {
            //交易金额不能为0
            $this->error("交易金额不能为0");
            return FALSE;
        }
        if (ceil($fUserScore) !== $fUserScore) {
            //不支持小数
            $this->error("不支持小数,您输入的是[{$fUserScore}]");
            return FALSE;
        }
        //执行远程API操作，返回当前余额
        $user_gscore_balance = NULL;
        $aSaveUserGScoreOrder = array();
        $ex = NULL;
        if (TRUE) {
            try {
                if ($apiType === 'api1') {
                    $user_gscore_balance = $this->transScoreAndGetBalance($username, $plat_type, $fUserScore, $client_transfer_id);
                } else if ($apiType === 're-api1') {
                    //因为上次和NG通信失败，导致status仍然是0，因此重试
                    try {
                        //首先确定订单是否存在
                        $aScoreStatus = $this->CheckTransUserScoreStatus($username, $plat_type, $client_transfer_id);
                        $aSaveUserGScoreOrder['money'] = $aScoreStatus['score'];
                        $user_gscore_balance = $aScoreStatus['after_score'];
                    } catch (\Exception $ex) {
                        
                    }
                    if ($this->lastRet['statusCode'] === '00' && $this->lastRet['message'] === '失败') {
                        //如果订单不存在就去执行
                        $user_gscore_balance = $this->transScoreAndGetBalance($username, $plat_type, $fUserScore, $client_transfer_id);
                    }
                } else if ($apiType === 'api2') {
                    $aPost = array();
                    $aPost['site_uid'] = $aRowUserGScorelog['site_uid'];
                    $aPost['plat_type'] = $plat_type;
                    $aPost['money'] = $fUserScore;
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
                $aSaveUserGScoreOrder['status'] = 1;
                $aSaveUserGScoreOrder['api_trans_balance'] = $user_gscore_balance;
            } catch (\Exception $e) {
                $ex = $e;
                $aSaveUserGScoreOrder['status'] = 0;
            }
            $aSaveUserGScoreOrder['api_trans_code'] = isset($this->lastRet['code']) ? $this->lastRet['code'] : '';
            $aSaveUserGScoreOrder['api_trans_msg'] = isset($this->lastRet['msg']) ? $this->lastRet['msg'] : '';
        }
        $oSql->where($aWhereUserGScoreOrder)->save($aSaveUserGScoreOrder);
        if ($ex !== NULL) {
            $oDb->commit();
            throw $ex;
        }
        if ($apiType === 'api1' || $apiType === 're-api1') {
            //首先确定这个订单号在站点积分记录是否存在
            $oSqlSiteCreditLog = $oDb->sql()->table('site_credit_log')->where($aWhereUserGScoreOrder);
            $aRow = $oSqlSiteCreditLog->find();
            if (empty($aRow)) {
                //只有不存在的情况下才去操作账变
                $aRowSite = $oDb->sql()->table('site')->where('id', $aRowUserGScorelog['site_id'])->lock()->find();
                //与用户操作金额刚好相反，用户余额增加的时候站点额度减少，用户余额减少的时候站点额度增加
                $iCreditAmount = -$fUserScore;
                $iCreditBalanceBefore = floatval($aRowSite['credit_balance']);
                $iCreditBalanceAfter = $iCreditBalanceBefore + $iCreditAmount;
                $aSaveSite = array('credit_balance' => $iCreditBalanceAfter);
                $oDb->sql()->table('site')->where('id', $aRowUserGScorelog['site_id'])->save($aSaveSite);
                $aAddSiteCreditlog = array();
                $aAddSiteCreditlog['site_id'] = $aRowUserGScorelog['site_id'];
                $aAddSiteCreditlog['site_uid'] = $aRowUserGScorelog['site_uid'];
                $aAddSiteCreditlog['username'] = $aRowUserGScorelog['username'];
                $aAddSiteCreditlog['plat_type'] = $aRowUserGScorelog['plat_type'];
                $aAddSiteCreditlog['trans_type'] = $aRowUserGScorelog['trans_type'];
                $aAddSiteCreditlog['client_transfer_id'] = $aRowUserGScorelog['client_transfer_id'];
                $aAddSiteCreditlog['title'] = $aRowUserGScorelog['title'];
                $aAddSiteCreditlog['credit_amount'] = $iCreditAmount;
                $aAddSiteCreditlog['credit_balance_before'] = $iCreditBalanceBefore;
                $aAddSiteCreditlog['credit_balance_after'] = $iCreditBalanceAfter;
                $oSqlSiteCreditLog->add($aAddSiteCreditlog);
            }
            $oDb->commit();
        }
        return $user_gscore_balance;
    }

}
