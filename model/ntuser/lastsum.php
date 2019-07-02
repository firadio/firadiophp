<?php

return function($oDb2, $username, $last_second) {
    $time_after = time() - $last_second;
    $field = 'SUM(cpu_seconds)cpu_seconds';
    $where = 'username=:username AND UNIX_TIMESTAMP(created)>:time_after';
    $param = array('username' => $username, 'time_after' => $time_after);
    $row = $oDb2->sql()->table('ntuser_process_log')->field($field)->where($where)->param($param)->find();
    return $row;
};
