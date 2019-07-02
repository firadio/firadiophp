<?php

return function($oDb, $id, $amount) {
    if (empty($amount)) {
        return FALSE;
    }
    $table = 'ntuser_balance';
    $field = 'id,balance';
    $where = array();
    $where['id'] = $id;
    //在锁住的情况下读取到余额
    $row_balance = $oDb->sql()->table($table)->field($field)->where($where)->lock()->find();
    if (empty($row_balance)) {
        return FALSE;
    }
    $data = array();
    $data['balance'] = doubleval($row_balance['balance']);
    $data['balance'] += $amount;
    $oDb->sql()->table($table)->where(array('id' => $row_balance['id']))->save($data);
    return TRUE;
};
