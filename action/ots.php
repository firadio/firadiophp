<?php

return function($oRes, $ots_firadio1, $a = 'Hello World') {
    $ots_response = $ots_firadio1->putRow('MyTable', array('test' => 112233));
    $oRes->assign('ots_response', $ots_response);
    $oRes->message($a);
};
