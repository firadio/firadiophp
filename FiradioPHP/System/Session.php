<?php

namespace FiradioPHP\System;

class Session {

    private $config = array();
    private $redis;

    public function __construct(array $setting) {
        $this->config = $setting['config'];
        $this->connect();
    }

    private function connect() {
        $this->redis = new \Redis();
        $this->redis->connect($this->config['host'], $this->config['port']);
        if (!empty($this->config['auth'])) {
            $this->redis->auth($this->config['auth']);
        }
    }

}
