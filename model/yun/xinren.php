<?php

return function($oDb2, $im_type, $im_account, $username) {
    $where = array();
    $where['WxUserName'] = $im_account;
    $row_location = $oDb2->sql()->table('location_trace')->where($where)->desc('id')->find();
    if (empty($row_location)) {
        $this->model_error('请开启定位功能，方法是点击右上角的图标进去，选择【设置】，打开【提供位置信息】');
    }
    if (time() - strtotime($row_location['created']) > 100) {
        $this->model_error('请开启定位功能，方法是点击右上角的图标进去，选择【设置】，打开【提供位置信息】');
    }
    $where = array();
    $where['deleted'] = NULL;
    $where['username'] = $username;
    $row_ntuser = $oDb2->sql()->table('ntuser_user')->where($where)->find();
    if (empty($row_ntuser)) {
        $msg = "您提供的Win帐号{$username}尚未添加到云平台";
        $msg .= "\r\n请到飞儿云平台(yun.firadio.net)添加Win帐号";
        $msg .= "\r\n要了解详情可咨询客服微信号: " . CONFIG_ADMIN_WX;
        $this->model_error($msg);
    }
    if (empty($row_ntuser['processed'])) {
        $msg = "您提供的Windows用户名[{$username}]\r\n尚未审核开通";
        $msg .= "\r\n要了解详情可咨询客服微信号: " . CONFIG_ADMIN_WX;
        $this->model_error($msg);
    }
    $user_id = $row_ntuser['id'];
    $username = $row_ntuser['username'];
    $where = array('im_account' => $im_account);
    //第一步判断IM
    $row = $oDb2->sql()->table('ntuser_xinren')->where($where)->find();
    if ($row) {
        $msg = "您的微信号已经取过[新人点数]了，请换别的微信号或则联系群主阿盛解决";
        return $msg;
    }
    //第二步判断帐号
    $where = array('username' => $username);
    $row = $oDb2->sql()->table('ntuser_xinren')->where($where)->find();
    if ($row) {
        $msg = "您的帐号已经领取过[新人点数]了，如果还不够用请联系群主阿盛QQ:309385018进行充值(1元=1万)";
        return $msg;
    }
    $award = 1000;
    $msg = "恭喜您获得[新人点数]{$award}秒";
    $data = array();
    $data['im_type'] = $im_type;
    $data['im_account'] = $im_account;
    $data['username'] = $username;
    $data['award'] = $award;
    $oDb2->sql()->table('ntuser_xinren')->add($data);
    $this->model_ntuser_balance_add($oDb2, $user_id, $username, $award, 'CPU', 'xinren', 60);
    $oDb2->commit();
    $cpu_balance_sum = $this->model_ntuser_balance_getuserdev($oDb2, $user_id, 'CPU');
    $msg .= "\r\n当前{$username}总点数为" . intval($cpu_balance_sum) . '秒';
    $cpu_credit_sum = $this->model_ntuser_balance_getcredit($oDb2, $user_id, 'CPU');
    $msg .= "\r\n总可用点数为" . intval($cpu_balance_sum + $cpu_credit_sum) . '秒';
    return $msg;
};
