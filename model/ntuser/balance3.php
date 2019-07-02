<?php

return function($oDb2, $username) {
    $where = array();
    $where['username'] = $username;
    $where['device'] = 'CPU';
    $rows_balance = $oDb2->sql()->table('ntuser_balance')->field('acctype,balance')->where($where)->select();
    $msg = "[{$username}]的";
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
        } else if ($acctype == 'signin') {
            $acctype = '签到';
        }
        $balance = intval($row_balance['balance']);
        if ($balance === 0) {
            continue;
        }
        if ($key != 0) {
            $msg .= '，';
        }
        $sum_balance += $balance;
        $msg .= "{$acctype}点数{$balance}秒\r\n";
    }
    $cpu_balance = intval($sum_balance);
    if ($key > 0) {
        $msg .= "，总点数{$cpu_balance}秒";
    }
    $last_second = 100;
    $row = $this->model_ntuser_lastsum($oDb2, $username, $last_second);
    $cpu_seconds = intval($row['cpu_seconds'] * 100) / 100;
    $pp = '您';
    if ($cpu_seconds > 0) {
        $msg .= "\r\n{$pp}当前CPU耗速{$cpu_seconds}每百秒";
        $msg .= "，预计可用";
        $bal_seconds = $cpu_balance * (100 / $row['cpu_seconds']);
        $msg .= $this->model_date_tohhmmss($bal_seconds);
    }
    return $msg;
};
