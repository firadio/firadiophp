<?php

return function($oRes, $qyapi) {
    //$oRes->assign('ret', $qyapi->appchat_create());
    $oRes->assign('a', $qyapi->appchat_send_text('feieryun1'));
};
