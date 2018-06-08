<?php

return function() {
    $config = require(__DIR__ . DS . 'db' . DS . 'default.php');
    $config['host'] = 'localhost';
    $config['port'] = '3306';
    $config['dbname'] = 'information_schema';
    $config['tablepre'] = '';
    $config['username'] = 'root';
    $config['password'] = '123456';
    return $config;
};
