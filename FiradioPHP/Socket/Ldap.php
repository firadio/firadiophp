<?php

namespace FiradioPHP\Socket;

class Ldap {

    private $aConfig;
    private $conn;

    public function __construct($conf) {
        $this->aConfig = $conf;
        $this->conn = ldap_connect($conf['host'], $conf['port']);
        if (!$this->conn) {
            die("Can't connect to LDAP server");
        }
    }

    public function getList($func_name, $param_type) {
        
    }

    private function error($message, $title = '提示') {
        $ex = new \Exception($message, -2);
        $ex->title = $title;
        throw $ex;
    }

}
