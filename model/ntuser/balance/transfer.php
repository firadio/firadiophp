<?php

return function($oDb2, $row_fromuser, $row_touser, $amount, $device = 'CPU', $acctype = 'basic', $level = 10) {
    $from_balance = $this->model_ntuser_balance_getuserone($oDb2, $row_fromuser['id'], $acctype, $device);
    $to_balance = $this->model_ntuser_balance_getuserone($oDb2, $row_touser['id'], $acctype, $device);
    if ($from_balance < $amount) {
        $this->model_error("您的{$row_fromuser['username']}(Win帐号)的[氪金点数]只有" . intval($from_balance));
    }
    $this->model_ntuser_balance_add($oDb2, $row_fromuser['id'], $row_fromuser['username'], -$amount, 'CPU', 'basic', 10);
    $this->model_ntuser_balance_add($oDb2, $row_touser['id'], $row_touser['username'], $amount, 'CPU', 'basic', 10);
    $data = array();
    $data['device'] = $device;
    $data['acctype'] = $acctype;
    $data['from_qquin'] = $row_fromuser['qquin'];
    $data['from_username'] = $row_fromuser['username'];
    $data['from_balance_before'] = $from_balance;
    $data['from_balance_after'] = $from_balance - $amount;
    $data['to_qquin'] = $row_touser['qquin'];
    $data['to_username'] = $row_touser['username'];
    $data['to_balance_before'] = $to_balance;
    $data['to_balance_after'] = $to_balance + $amount;
    $data['amount'] = $amount;
    $oDb2->sql()->table('ntuser_transfer')->add($data);
    $msg = '[氪金点数]转帐成功！';
    $msg .= "\r\n您的[远程帐号]{$row_fromuser['username']}的[氪金点数]余额由" . intval($data['from_balance_before']) . "到" . intval($data['from_balance_after']);
    $msg .= "\r\n对方[远程帐号]{$row_touser['username']}的[氪金点数]余额为" . intval($data['to_balance_before']) . "到" . intval($data['to_balance_after']);
    return $msg;
};
