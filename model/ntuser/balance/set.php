<?php

return function($oDb2, $user_id, $username, $amount, $device = 'CPU', $acctype = 'basic', $level = 10) {
    if (empty($user_id)) {
        return false;
    }
    if (empty($username)) {
        return false;
    }
    if (empty($amount)) {
        return false;
    }
    $table = 'ntuser_balance';
    $field = 'id,balance';
    $where = array();
    $where['user_id'] = $user_id;
    $where['device'] = $device;
    $where['acctype'] = $acctype;
    $row_balance = $oDb2->sql()->table($table)->field($field)->where($where)->lock()->find();
    $data = array();
    if ($row_balance) {
        $data['balance'] = $amount;
        $oDb2->sql()->table($table)->where(array('id' => $row_balance['id']))->save($data);
    } else {
        $data['user_id'] = $user_id;
        $data['username'] = $username;
        $data['device'] = $device;
        $data['acctype'] = $acctype;
        $data['level'] = $level;
        $data['balance'] = $amount;
        $oDb2->sql()->table($table)->add($data);
    }
    return true;
};
