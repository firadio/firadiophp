<?php

return function($oDb2, $qquin, $username) {
    $data = array();
    $data['create_qquin'] = $qquin;
    $data['request_action'] = 'logout';
    $data['request_username'] = $username;
    $oDb2->sql()->table('ntuser_action')->add($data);
};
