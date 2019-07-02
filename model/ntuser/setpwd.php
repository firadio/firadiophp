<?php

return function($oDb2, $qquin, $username, $password) {
    if (strtolower($username) == 'adm') {
        return;
    }
    if (strtolower($username) == 'asheng') {
        return;
    }
    if (strtolower($username) == 'administrator') {
        return;
    }
    $data = array();
    $data['fetched'] = NULL;
    $data['requested'] = 'CURRENT_TIMESTAMP()';
    $data['request_action'] = 'setpwd';
    $data['password'] = $password;
    $where = array();
    $where['qquin'] = $qquin;
    $where['username'] = $username;
    $oDb2->sql()->table('ntuser_user')->where($where)->save($data);
};
