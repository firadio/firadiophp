<?php

return function($oDb2, $qquin, $at_qquin, $cpu_seconds) {
    return "早期版本的转账功能已停用";
    if ($cpu_seconds < 100) {
        return "单笔转帐[点数]最少100秒";
    }
    $limit = 10000;
    if ($cpu_seconds > $limit) {
        return "单笔转帐[点数]最大上限{$limit}秒";
    }
    $row_fromuser = $this->model_ntuser_userinfo($oDb2, $qquin);
    if (empty($row_fromuser)) {
        return "您尚未到云平台登记[Win帐号]";
    }
    if ($row_fromuser['cpu_balance'] < $cpu_seconds) {
        return "您的[Win帐号]{$row_fromuser['username']}的[CPU运算点数]只有" . intval($row_fromuser['cpu_balance']);
    }
    $row_touser = $this->model_ntuser_userinfo($oDb2, $at_qquin);
    if (empty($row_touser)) {
        return "对方尚未到云平台登记[Win帐号]";
    }
    $data = array();
    $data['cpu_balance'] = doubleval($row_fromuser['cpu_balance']) - $cpu_seconds;
    $from_balance_after = intval($data['cpu_balance']);
    $oDb2->sql()->table('ntuser_user')->where(array('id' => $row_fromuser['id']))->save($data);
    $data = array();
    $data['cpu_balance'] = doubleval($row_touser['cpu_balance']) + $cpu_seconds;
    $to_balance_after = intval($data['cpu_balance']);
    $oDb2->sql()->table('ntuser_user')->where(array('id' => $row_touser['id']))->save($data);
    $data = array();
    $data['type'] = 'cpu_seconds';
    $data['from_qquin'] = $qquin;
    $data['from_username'] = $row_fromuser['username'];
    $data['from_balance'] = $row_fromuser['cpu_balance'];
    $data['to_qquin'] = $at_qquin;
    $data['to_username'] = $row_touser['username'];
    $data['to_balance'] = $row_touser['cpu_balance'];
    $data['cpu_seconds'] = $cpu_seconds;
    $oDb2->sql()->table('ntuser_transfer')->add($data);
    $oDb2->commit();
    $msg = '[CPU运算点数]转帐成功！';
    $msg .= "\r\n您的[Win帐号]{$row_fromuser['username']}的[CPU运算点数]余额为{$from_balance_after} (" . intval($row_fromuser['cpu_balance']) . " - {$cpu_seconds})";
    $msg .= "\r\n对方[Win帐号]{$row_touser['username']}的[CPU运算点数]余额为{$to_balance_after} (" . intval($row_touser['cpu_balance']) . " + {$cpu_seconds})";
    return $msg;
};
