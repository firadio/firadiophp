<?php

return function($oDb2, $qquin, $username) {
    $row = $this->model_ntuser_userinfo($oDb2, $username);
    if (empty($row)) {
        return '签到失败，' . $nousermsg;
    }
    $user_id = $row['id'];
    $username = $row['username'];
    $where = array('date' => date('Y-m-d'), 'im_account' => $qquin);
    $row = $oDb2->sql()->table('ntuser_signin')->where($where)->find();
    $data = array();
    if ($row) {
        // return '签过了';
        //return "您今天已签到过了.";
        $cpu_balance_sum = $this->model_ntuser_balance_getuserdev($oDb2, $user_id, 'CPU');
        $cpu_balance_sum = intval($cpu_balance_sum);
        return "您今天已签到过了，\r\n您的[Win帐号]{$username}当前[CPU运算点数]总余额为{$cpu_balance_sum}秒";
    }
    $where = array();
    $where['user_id'] = $user_id;
    $where['device'] = 'CPU';
    $where['acctype'] = 'free';
    $row_balance = $oDb2->sql()->table('ntuser_balance')->field('id,balance')->where($where)->lock()->find();
    $cpu_balance_before = $cpu_balance = 0;
    if ($row_balance) {
        $cpu_balance_before = $cpu_balance = doubleval($row_balance['balance']);
    }
    $cpu_balance_before_i = intval($cpu_balance_before);
    if (0 && $cpu_balance_before > 2000) {
        // return '已上限';
        return "签到失败，您{$username}的[CPU运算点数]已达上限，当前{$cpu_balance_before_i}秒";
    }
    if ($cpu_balance_before < 0) {
        //return "签到失败，您的免费[CPU运算点数]为负数({$cpu_balance_before_i}秒)，请先[复活]";
    }
    $msg = "签到成功";
    $award = mt_rand(100, 300);
    $cpu_balance += $award;
    $msg .= "\r\n您的[Win帐号]{$username}获得免费账户的[CPU运算点数]随机奖励{$award}秒";
    $award2 = 10;
    if (1) {
        $where = array('date' => date('Y-m-d'));
        $row = $oDb2->sql()->table('ntuser_signin')->field('COUNT(*)date_num')->where($where)->find();
        $date_num = doubleval($row['date_num']) + 1;
        if ($date_num < 10) {
            $award2 = 400;
        } else if ($date_num < 20) {
            $award2 = 300;
        } else if ($date_num < 50) {
            $award2 = 200;
        } else if ($date_num < 100) {
            $award2 = 100;
        }
        $msg .= "\r\n您是第{$date_num}个签到的用户，系统额外再奖励{$award2}秒";
        $cpu_balance += $award2;
    }
    $cpu_balance_i = intval($cpu_balance);
    $msg .= "\r\n您当前免费账户的[CPU运算点数]余额为{$cpu_balance_i}秒({$cpu_balance_before_i}+" . ($award + $award2) . ")";
    $data = array();
    $data['date'] = date('Y-m-d');
    $data['im_account'] = $qquin;
    $data['username'] = $username;
    $data['award'] = $award;
    $data['award2'] = $award2;
    $data['cpu_balance_before'] = $cpu_balance_before;
    $data['cpu_balance_after'] = $cpu_balance;
    $oDb2->sql()->table('ntuser_signin')->add($data);
    $data = array();
    $data['balance'] = $cpu_balance;
    if ($row_balance) {
        $oDb2->sql()->table('ntuser_balance')->where(array('id' => $row_balance['id']))->save($data);
    } else {
        $data['user_id'] = $user_id;
        $data['username'] = $username;
        $data['device'] = 'CPU';
        $data['acctype'] = 'free';
        $data['level'] = 50;
        $oDb2->sql()->table('ntuser_balance')->add($data);
    }
    $oDb2->commit();
    // $msg = '签到成功';
    return $msg;
};
