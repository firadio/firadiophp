<?php

namespace FiradioPHP\Database;

use \Exception;
use FiradioPHP\F;

class Redis {

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

    public function sAdd($name, $value) {
        if (is_array($value)) {
            if (count($value) === 0) return;
            array_unshift($value, $name);
            return call_user_func_array([$this->redis, 'sAdd'], $value);
        }
        return $this->redis->sAdd($name, $value);
    }

    public function sCard($name) {
        // 获得总数
        return $this->redis->sCard($name);
    }

    public function del($name) {
        return $this->redis->del($name);
    }

    public function sRandMember($name, $count = 1) {
        return $this->redis->sRandMember($name, $count);
    }

    public function set($name, $value) {
        return $this->redis->set($name, $value);
    }

    public function get($name) {
        return $this->redis->get($name);
    }

    public function delKeys($keyname) {
        $keys = $this->redis->KEYS($keyname);
        foreach ($keys as $key) {
            $this->redis->DEL($key);
        }
    }

    public function HSET($name, $key, $val) {
        return $this->redis->HSET($name, $key, $val);
    }

    public function HGET($name, $key) {
        return $this->redis->HGET($name, $key);
    }

    public function HDEL($name, $key) {
        return $this->redis->HDEL($name, $key);
    }

    public function Keys($keyname) {
        $keys = $this->redis->KEYS($keyname);
        return $keys;
    }

    public function zCount($keyname, $start, $end) {
        $i = $this->redis->zCount($keyname, $start, $end);
        return $i;
    }

    public function hGetAll($keyname) {
        $row = $this->redis->hGetAll($keyname);
        if ($row === FALSE) $row = array();
        return $row;
    }

}
