<?php

return function($oRes, $mysql_feieryun, $a = 'Hello World') {
    $rows = $mysql_feieryun->sql()->table('PROCESSLIST')->select();
    $oRes->assign('rows', $rows);
    $oRes->message($a);
};
