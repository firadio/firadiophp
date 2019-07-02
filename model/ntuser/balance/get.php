<?php

return function($oDb2, $user_id, $device, $acctype) {
    $where = array();
    $where['user_id'] = $user_id;
    $where['device'] = 'CPU';
    $where['acctype'] = 'free';
    $row_balance = $oDb2->sql()->table('ntuser_balance')->field('id,balance')->where($where)->lock()->find();
    $cpu_balance_before = $cpu_balance = 0;
    if ($row_balance) {
        $cpu_balance_before = $cpu_balance = doubleval($row_balance['balance']);
    }
};
