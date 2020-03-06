<?php

return function($oDb2, $im_account, $content) {
    var_dump($content);
    if(!isset($GLOBALS['clients'])) {
        $GLOBALS['clients']=array();
    }
    if(!isset($GLOBALS['clients'][$im_account])) {
        $GLOBALS['clients'][$im_account]=array(
            'ctrl'=>''
        );
    }
    $UD=&$GLOBALS['clients'][$im_account];
    $msgkey = trim($content);
    $msgkey = str_replace("\r", '', $msgkey);
    $msgkey = str_replace("\n", '', $msgkey);
    $pattern = "/^(签到|来了){1}[\s]{0,10}([\w]{1,20})([@][\w\.]{3,15})?$/";
    if (preg_match($pattern, $msgkey, $matches)) {
        $ntacc_username = $matches[2];
        //var_dump($matches);
        $out = $this->model_yun_signin($oDb2, 'wechat', $im_account, $ntacc_username, $matches[1]);
        return $out;
    }
    if (strpos($content, '签到') !== false) {
        return '请在“签到”后带上您的Windows用户名，例如：签到asheng';
    }
    $pattern = "/^(新人点数){1}[\s]{0,10}([\w]{1,20})([@][\w\.]{3,15})?$/";
    if (preg_match($pattern, $msgkey, $matches)) {
        $ntacc_username = $matches[2];
        //var_dump($matches);
        $out = $this->model_yun_xinren($oDb2, 'wechat', $im_account, $ntacc_username);
        return $out;
    }
    if (strpos($content, '新人点数') !== false) {
        return '请在[新人点数]后带上您的Windows用户名，例如：新人点数asheng';
    }
    if (strpos($content, '充值') !== false) {
        //$row = $oDb2->sql()->table('wechat_media')->field('media_id')->where(array('id' => 1))->find();
        //return 'image:' . $row['media_id'];
        return '请进入下面链接查看充值方法' . "\r\n" . 'wx.anan.cc';
        return '请进入下面链接查看充值方法' . "\r\n" . 'http://yun.firadio.net/#/pay/wechat';
    }
    $pattern = "/激活((帐|账)号)?/";
    if (preg_match($pattern, $msgkey)) {
        $msg = '请[充值]任意金额即可开通您的Windows帐号，至少3元以上。';
        $msg .= "\r\n请使用[充值]指令获取微信付款二维码";
        return $msg;
    }

    $pattern = "/端口映射|portmap/";
    if (preg_match($pattern, $msgkey)) {
        $UD['ctrl']='portmap';
        $msg = '';
        //$msg .= "您尚未申请过公网端口";
        $msg .= "请输入您要的公网端口号";
        $msg .= "\r\n端口范围10000-19999(1xxxx)";
        return $msg;
    }
    if($UD['ctrl']==='portmap'){
        $pattern = "/^(add|del){1}[\s\+]{0,10}([\d]{1,5})$/";
        if (preg_match($pattern, $msgkey)) {
            $action=$matches[1];
            $port=$matches[2];
//            $err = $this->model_yun_portmap_checkport($oDb2, $im_account, $port);
            if($err){return $err;}
            $UD['action']=$action;
            $msg = '请输入您要绑定的主机和端口';
            return $msg;
        }
    }
    $pattern = "/^我的QQ\D{0,8}(\d{5,10})\D{0,3}/i";
    if (preg_match($pattern, $msgkey, $matches)) {
        $data = array('WxUserName' => $im_account);
        $row = $oDb2->sql()->table('weixin_user')->where($data)->find();
        $data['qqself'] = $matches[1];
        if ($row) {
            unset($data['WxUserName']);
            $oDb2->sql()->table('weixin_user')->where(array('id' => $row['id']))->save($data);
        } else {
            $oDb2->sql()->table('weixin_user')->add($data);
        }
        $oDb2->commit();
        $oDb2->beginTransaction();
        return '系统已记录您的QQ号:' . $matches[1];
    }
    $pattern = "/^我的客服\D{0,8}(\d{5,10})\D{0,3}/i";
    if (preg_match($pattern, $msgkey, $matches)) {
        $data = array('WxUserName' => $im_account);
        $row = $oDb2->sql()->table('weixin_user')->where($data)->find();
        $data['qqkefu'] = $matches[1];
        if ($row) {
            unset($data['WxUserName']);
            $oDb2->sql()->table('weixin_user')->where(array('id' => $row['id']))->save($data);
        } else {
            $oDb2->sql()->table('weixin_user')->add($data);
        }
        $oDb2->commit();
        $oDb2->beginTransaction();
        return '系统已记录您的专属客服QQ号:' . $matches[1];
    }
    return '';
};

