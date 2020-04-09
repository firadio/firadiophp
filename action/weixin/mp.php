<?php

return function($oWechat, $oDb2, $oRes, $echostr, $signature, $timestamp, $nonce, $sRawContent) {
    if ($signature !== $oWechat->getSignature($timestamp, $nonce)) {
        $this->error('invalid signature');
    }
    //var_dump('signature OK');
    if (!empty($echostr)) {
        $oRes->end($echostr);
        return;
    }
    $oWechat->loadXML($sRawContent);
    //echo $oWechat->getRawContent();
    $Event = $oWechat->request['Event'];
    if ($Event === 'subscribe') {
        $msg = '欢迎关注[飞儿云平台]，请使用[功能]指令获取操作菜单';
        //$msg .= "\r\n撩妹话术功能已开启";
        $oRes->end($oWechat->getResponse($msg));
        return;
    }
    if ($Event === 'unsubscribe') {
        $oRes->end($oWechat->getResponse('bye bye'));
        return;
    }
    // file_put_contents(__DIR__ . DS . '1.txt', json_encode($oWechat->request) . "\r\n", FILE_APPEND);
    if ($Event === 'LOCATION') {
        $data = array();
        $data['WxUserName'] = $oWechat->request['FromUserName'];
        $data['Latitude'] = $oWechat->request['Latitude'];
        $data['Longitude'] = $oWechat->request['Longitude'];
        $oDb2->sql()->table('location_trace')->add($data);
        $oDb2->commit();
        $oDb2->beginTransaction();
        //$oRes->end($oWechat->getResponse($oWechat->request['Latitude'] . ',' . $oWechat->request['Longitude']));
        $oRes->end('');
        return;
    }
    $EventKey = $oWechat->request['EventKey'];
    if ($EventKey === 'SIGNIN') {
        $oRes->end($oWechat->getResponse('请发送“签到”后面跟上您的云主机英文用户名'));
        return;
    }
    if ($EventKey === 'LOGIN') {
        $oRes->end($oWechat->getResponse('我们云平台网址是yun.firadio.net'));
        return;
    }
    $reqContent = $oWechat->request['Content'];
    if ($reqContent === '充值') {
        $oRes->end($oWechat->getResponseNews('CPU点数在线充值', "微信支付 (1元=1万点数)\r\nhttp://wx.anan.cc", 'http://wx.anan.cc'));
        return;
    }
    if ($reqContent === '退款') {
        $oRes->end($oWechat->getResponse('你的退款凭证是:' . $oWechat->request['FromUserName']));
        return;
    }
    if ($reqContent === '功能') {
        $msg = '您可以使用以下的指令';
        $msg .= "\r\n" . '[激活帐号] 查看如何激活帐号';
        $msg .= "\r\n" . '[新人点数] 首次领取1000点数';
        $msg .= "\r\n" . '[签到] 每日领取500点数';
        $msg .= "\r\n" . '[充值] 1元=1万点数';
        $msg .= "\r\n" . '如有疑问请加阿盛QQ:' . CONFIG_ADMIN_QQ . '，或微信:' . CONFIG_ADMIN_WX;
        //$msg .= "\r\n" . '也可以电话联系18816975390';
    }
    if (!empty($oWechat->request['Recognition'])) {
        $reqContent = $oWechat->request['Recognition'];
        $msg = '您说的是：' . $reqContent;
        $oRes->end($oWechat->getResponse($msg));
        return;
    }
    $msg = '';
    try {
        $msg = $this->model_yun_wechat($oDb2, $oWechat->request['FromUserName'], $reqContent);
    } catch (FiradioPHP\Routing\ModelException $ex) {
        $msg = $ex->getMessage();
    }
    if (0 && $msg === '') {
        try {
            $msg = $this->model_yun_huashu($oDb6, $oWechat->request['FromUserName'], $reqContent, $oWechat->request['MediaId']);
        } catch (FiradioPHP\Routing\ModelException $ex) {
            $msg = $ex->getMessage();
        }
    }
    if ($msg === '' && !empty($oWechat->request['MediaId'])) {
        $msg = 'MediaId=' . $oWechat->request['MediaId'];
    }
    if (0 && $msg === '') {
        $msg = '您可以使用以下的指令';
        $msg .= "\r\n" . '[激活帐号] 查看如何激活帐号';
        $msg .= "\r\n" . '[新人点数] 首次领取1000点数';
        $msg .= "\r\n" . '[签到] 每日领取500点数';
        $msg .= "\r\n" . '[充值] 1元=1万点数';
        $msg .= "\r\n" . '如有疑问请加阿盛QQ:' . CONFIG_ADMIN_QQ . '，或微信:' . CONFIG_ADMIN_WX;
        //$msg .= "\r\n" . '也可以电话联系18816975390';
    }
    $oRes->end($oWechat->getResponse($msg));
};
