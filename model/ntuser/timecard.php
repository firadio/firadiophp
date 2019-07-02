<?php

return function($oDb2, $qquin, $card_id, $card_password) {
    $where = array();
    $where['id'] = $card_id;
    $field = 'id,used,password,cpu_seconds';
    $row_timecard = $oDb2->sql()->table('ntuser_timecard')->field($field)->where($where)->lock()->find();
    if (empty($row_timecard)) {
        return "您输入的点卡卡号{$card_id}不存在";
    }
    if ($row_timecard['used']) {
        return "您输入的点卡卡号{$card_id}已被使用过了";
    }
    if ($row_timecard['deleted']) {
        return "您输入的点卡卡号{$card_id}已被作废";
    }
    if ($row_timecard['password'] != $card_password) {
        return "您输入的点卡卡号和密码不匹配";
    }
    $row_userinfo = $this->model_ntuser_userinfo($oDb2, $qquin);
    if (empty($row_userinfo)) {
        return "您尚未到云平台登记[Win帐号]";
    }
    $cpu_balance_before = $cpu_balance = doubleval($row_userinfo['cpu_balance']);
    $cpu_balance += intval($row_timecard['cpu_seconds']);
    $data = array();
    $data['cpu_balance'] = $cpu_balance;
    $oDb2->sql()->table('ntuser_user')->where(array('id' => $row_userinfo['id']))->save($data);
    $data = array();
    $data['used'] = 'CURRENT_TIMESTAMP()';
    $data['to_qquin'] = $qquin;
    $data['to_username'] = $row_userinfo['username'];
    $oDb2->sql()->table('ntuser_timecard')->where(array('id' => $row_timecard['id']))->save($data);
    $oDb2->commit();
    $msg = "您的[Win帐号]{$row_userinfo['username']}已成功充值[CPU运算点数]" . intval($row_timecard['cpu_seconds']) . "秒";
    $msg .= "\r\n当前余额为" . intval($cpu_balance) . "秒（" . intval($cpu_balance_before) . "+" . intval($row_timecard['cpu_seconds']) . "）";
    return $msg;
};
