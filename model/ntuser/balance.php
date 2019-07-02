<?php

return function($oDb2, $qquin, $at_qquin) {
    $pp = '您';
    if ($at_qquin) {
        $pp = '他';
        $qquin = $at_qquin;
    }
    $row = $this->model_ntuser_userinfo($oDb2, $qquin);
    if (empty($row)) {
        if ($at_qquin) {
            return '查询失败，他尚未在云平台登记Windows独立帐号';
        }
        return '查询失败，' . $nousermsg;
    }
    $username = $row['username'];
    $where = array();
    $where['user_id'] = $row['id'];
    $where['device'] = 'CPU';
    $rows_balance = $oDb2->sql()->table('ntuser_balance')->field('acctype,balance')->where($where)->select();
    $msg = "{$pp}的[{$username}]";
    $sum_balance = 0;
    foreach ($rows_balance as $key => $row_balance) {
        $acctype = $row_balance['acctype'];
        if ($acctype == 'free') {
            $acctype = '免费';
        } else if ($acctype == 'hezuo') {
            $acctype = '合作';
        } else if ($acctype == 'basic') {
            $acctype = '氪金';
        } else if ($acctype == 'huodong') {
            $acctype = '活动';
        }
        $balance = intval($row_balance['balance']);
        if ($key != 0) {
            $msg .= '，';
        }
        $sum_balance += $balance;
        $msg .= "{$acctype}点数{$balance}秒";
    }
    $cpu_balance = intval($sum_balance);
    if ($key > 0) {
        $msg .= "，合计{$cpu_balance}秒";
    }
    $last_second = 100;
    $row = $this->model_ntuser_lastsum($oDb2, $username, $last_second);
    $cpu_seconds = intval($row['cpu_seconds'] * 100) / 100;
    if ($cpu_seconds > 0) {
        //$msg .= "\r\n{$pp}在最近{$last_second}秒内消耗CPU时间{$cpu_seconds}秒";
        $msg .= "\r\n{$pp}当前CPU耗速{$cpu_seconds}每百秒";
        $msg .= "，预计可用";
        $bal_seconds = $cpu_balance * (100 / $row['cpu_seconds']);
        $msg .= $this->model_date_tohhmmss($bal_seconds);
        //$msg .= "(大约)";
    }
    return $msg;
};
