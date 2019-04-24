<?php

return array(
    'class' => '\FiradioPHP\Database\Pdo',
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'dbname' => 'information_schema',
    'tablepre' => 'pre_',
    'username' => 'root',
    'password' => call_user_func(function() {
                $password = '';
                $file = __DIR__ . DS . '~password.txt';
                if (is_file($file)) {
                    $password = file_get_contents($file);
                }
                $password = str_replace("\n", '', $password);
                $password = str_replace("\r", '', $password);
                return $password;
            }),
    'options' => array(
        'MYSQL_ATTR_INIT_COMMAND' => 'set names utf8mb4',
    /*
      'MYSQL_ATTR_SSL_KEY' => __DIR__ . DS . 'client-key.pem',
      'MYSQL_ATTR_SSL_CERT' => __DIR__ . DS . 'client-cert.pem',
      'MYSQL_ATTR_SSL_CA' => __DIR__ . DS . 'ca.pem',
     */
    ),
    'attributes' => array(
        'ATTR_AUTOCOMMIT' => FALSE,
        'ATTR_ERRMODE' => 'ERRMODE_EXCEPTION',
        'MYSQL_ATTR_USE_BUFFERED_QUERY' => TRUE,
    )
);
