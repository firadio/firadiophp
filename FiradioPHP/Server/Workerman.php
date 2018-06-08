<?php

namespace FiradioPHP\Server;

use FiradioPHP\F;

/**
 * Description of workerman
 *
 * @author asheng
 */
class workerman {

    //put your code here
    function action_path($server) {
        //取得用户要执行的路径
        $iPos = strpos($server['REQUEST_URI'], '?');
        return is_numeric($iPos) ? substr($server['REQUEST_URI'], 0, $iPos) : $server['REQUEST_URI'];
    }

    function getIpAddr($server) {
        //取得用户的IP地址
        if (isset($server['HTTP_X_REAL_IP'])) {
            return $server['HTTP_X_REAL_IP'];
        }
        if (isset($server['REMOTE_ADDR'])) {
            return $server['REMOTE_ADDR'];
        }
        return '0.0.0.0';
    }

    function execute_time($begin_time) {
        //计算执行时间
        $execute_time = (microtime(true) - $begin_time) * 1000;
        if ($execute_time < 10) {
            return number_format($execute_time, 2);
        }
        if ($execute_time < 100) {
            return number_format($execute_time, 1);
        }
        return intval($execute_time);
    }

}
