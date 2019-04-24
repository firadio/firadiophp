<?php

return function($oRes, $mqtt, $a = 'Hello World') {
    // $rows = ;
    // $oRes->assign('rows', $rows);
    $oRes->message($a);
};
