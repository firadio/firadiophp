<?php

return function($oRes, $oDb1, $a = 'Hello World') {
    $rows = $oDb1->sql()->table('PROCESSLIST')->select();
    $oRes->assign('rows', $rows);
    $oRes->message($a);
};
