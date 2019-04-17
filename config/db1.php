<?php

return function() {
    $config = require(__DIR__ . DS . 'db' . DS . 'default.php');
    $config['host'] = 'vps.firadio.net';
    $config['port'] = '3324';
    $config['dbname'] = 'information_schema';
    $config['tablepre'] = '';
    $config['username'] = 'feieryun';
    $config['password'] = 'feieryun';
    return $config;
};
