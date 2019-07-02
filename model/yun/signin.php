<?php

return function($oDb2, $im_type, $im_account, $username, $msgkey) {
    $where = array();
    $where['WxUserName'] = $im_account;
    $row_location = $oDb2->sql()->table('location_trace')->where($where)->desc('id')->find();
    if (empty($row_location)) {
        $this->model_error('请开启定位功能，方法是点击右上角的图标进去，选择【设置】，打开【提供位置信息】');
    }
    $timeout = time() - strtotime($row_location['created']);
    if ($timeout > 1000) {
        $this->model_error('请勿关闭定位功能，在【设置】里的【提供位置信息】要一直开着，距离上次采集定位已过去' . $timeout . '秒');
    }
    $where = array();
    $where['deleted'] = NULL;
    $where['username'] = $username;
    $row_ntuser = $oDb2->sql()->table('ntuser_user')->where($where)->find();
    if (empty($row_ntuser)) {
        $msg = "您提供的Win帐号{$username}尚未添加到云平台";
        $msg .= "\r\n请到飞儿云平台(yun.firadio.net)添加Win帐号";
        $msg .= "\r\n要了解详情可咨询客服微信号:" . CONFIG_ADMIN_WX;
        $this->model_error($msg);
    }
    if (empty($row_ntuser['opened'])) {
        $msg = "您提供的Windows用户名[{$username}]\r\n尚未审核开通";
        $msg .= "\r\n要了解详情可咨询客服微信号:" . CONFIG_ADMIN_WX;
        $this->model_error($msg);
    }
    $user_id = $row_ntuser['id'];
    $username = $row_ntuser['username'];
    $cpu_balance_before = $this->model_ntuser_balance_getuserdev($oDb2, $user_id, 'CPU');
    $cpu_balance_signin = $this->model_ntuser_balance_getuserone($oDb2, $user_id, 'signin', 'CPU');
    $cpu_credit_sum = $this->model_ntuser_balance_getcredit($oDb2, $user_id, 'CPU');
    if ($cpu_balance_signin >= 1000 && $msgkey == '签到') {
        //$msg = "{$username}点数为" . intval($cpu_balance_before) . "秒，\r\n您的点数已经比较多了，需要通过【二级签到密码】进行签到，\r\n请联系客服微信号" . CONFIG_ADMIN_WX;
        //$msg = "{$username}的签到点数为" . intval($cpu_balance_signin) . "秒，\r\n您的签到点数已经大于1000了，总可用点数为" . intval($cpu_balance_before + $cpu_credit_sum) . "秒，如果还不够用请进行充值wx.anan.cc (1元=1万点数)";
        //return $msg;
    }
/*
    if ($cpu_balance_signin >= 1000) {
        $msg = "{$username}点数为" . intval($cpu_balance_before) . "秒，\r\n您的签到点数已经大于1000了，请点击这里充值wx.anan.cc(1元=1万)";
        return $msg;
    }//*/
    $award = 500;
    if ($cpu_balance_signin >= 2000) {
        $award = 500;
    } else
    if ($cpu_balance_signin >= 1000) {
        $award = 500;
    }
    $where = array('date' => date('Y-m-d'), 'im_account' => $im_account);
    $row = $oDb2->sql()->table('ntuser_signin')->where($where)->find();
    if ($row) {
        $msg = "您今天已签到过了，\r\n每个微信号每天仅可签到一次，\r\n{$username}当前总点数为" . intval($cpu_balance_before) . "秒";
        $msg .= "\r\n总可用点数为" . intval($cpu_balance_before + $cpu_credit_sum) . '秒';
        //$msg .= "\r\n现在添加客服微信号" . CONFIG_ADMIN_WX . ",可申请加入微信群";
        return $msg;
    }
    $cpu_balance_after = $cpu_balance_before;
    $cpu_balance_after += $award;
    $msg = "恭喜您获得[签到点数]{$award}秒";
    $data = array();
    $data['date'] = date('Y-m-d');
    $data['im_type'] = $im_type;
    $data['im_account'] = $im_account;
    $data['username'] = $username;
    $data['award'] = $award;
    $data['award2'] = 0;
    $data['cpu_balance_before'] = $cpu_balance_before;
    $data['cpu_balance_after'] = $cpu_balance_after;
    $oDb2->sql()->table('ntuser_signin')->add($data);
    $this->model_ntuser_balance_add($oDb2, $user_id, $username, $award, 'CPU', 'signin', 60);
    $oDb2->commit();
    $cpu_balance_sum = $this->model_ntuser_balance_getuserdev($oDb2, $user_id, 'CPU');
    $cpu_balance_signin = $this->model_ntuser_balance_getuserone($oDb2, $user_id, 'signin', 'CPU');
    $msg .= "\r\n当前{$username}签到点数为" . intval($cpu_balance_signin) . '秒';
    $msg .= "\r\n当前{$username}总点数为" . intval($cpu_balance_sum) . '秒';
    $cpu_credit_sum = $this->model_ntuser_balance_getcredit($oDb2, $user_id, 'CPU');
    $msg .= "\r\n总可用点数为" . intval($cpu_balance_sum + $cpu_credit_sum) . '秒';
    //$msg .= "\r\n现在添加客服微信号" . CONFIG_ADMIN_WX . "可申请加入微信群";
    return $msg;
};
