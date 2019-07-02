<?php

return function($oDb2, $user_id, $acctype = 'free', $device = 'CPU') {
    $where = array();
    $where['user_id'] = $user_id;
    $where['device'] = $device;
    $where['acctype'] = $acctype;
    $row_balance = $oDb2->sql()->table('ntuser_balance')->field('balance')->where($where)->find();
    if (empty($row_balance)) {
        return 0;
    }
    return doubleval($row_balance['balance']);
};
