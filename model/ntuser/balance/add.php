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
    $data['balance'] = $amount;
    if ($row_balance) {
        $data['balance'] += doubleval($row_balance['balance']);
    }
    if ($data['balance'] < 0) {
        $this->model_error('交易后余额不能为负数');
    }
    if ($row_balance) {
        $oDb2->sql()->table($table)->where(array('id' => $row_balance['id']))->save($data);
        return true;
    }
    $data['user_id'] = $user_id;
    $data['username'] = $username;
    $data['device'] = $device;
    $data['acctype'] = $acctype;
    $data['level'] = $level;
    $oDb2->sql()->table($table)->add($data);
    return false;
};
