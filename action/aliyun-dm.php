<?php

return function($oRes, $dm, $a = 'Hello World') {
    if ($dm->sendMail('86333956@qq.com', '发送测试内容')) {
        $a = '发送成功Success';
    }
    $oRes->message($a);
};
