<?php

return function($oRes, $qyapi) {
    $oRes->assign('ret', $qyapi->appchat_create());
};
