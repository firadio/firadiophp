<?php

namespace FiradioPHP\Database;

class Redis {

    private $config = array();
    private $redis;
    private $keypre = '';

    public function __construct(array $setting) {
        $this->config = $setting['config'];
        $this->keypre = isset($this->config['keypre']) ? $this->config['keypre'] : '';
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
            if (count($value) === 0) {
                return;
            }
            array_unshift($value, $this->keypre . $name);
            return call_user_func_array([$this->redis, 'sAdd'], $value);
        }
        return $this->redis->sAdd($this->keypre . $name, $value);
    }

    public function sCard($name) {
        // 获得总数
        return $this->redis->sCard($this->keypre . $name);
    }

    public function del($name) {
        return $this->redis->del($this->keypre . $name);
    }

    public function sRandMember($name, $count = 1) {
        return $this->redis->sRandMember($this->keypre . $name, $count);
    }

    public function set($name, $value, $param3 = NULL) {
        // 参考 https://github.com/phpredis/phpredis/#set
        if ($param3 !== NULL) {
            return $this->redis->set($this->keypre . $name, $value, $param3);
        }
        return $this->redis->set($this->keypre . $name, $value);
    }

    public function get($name) {
        return $this->redis->get($this->keypre . $name);
    }

    public function delKeys($keyname) {
        $keys = $this->redis->KEYS($this->keypre . $keyname);
        foreach ($keys as $key) {
            $this->redis->DEL($key);
        }
    }

    public function HSET($name, $key, $val) {
        return $this->redis->HSET($this->keypre . $name, $key, $val);
    }

    public function HGET($name, $key) {
        return $this->redis->HGET($this->keypre . $name, $key);
    }

    public function HDEL($name, $key) {
        return $this->redis->HDEL($this->keypre . $name, $key);
    }

    public function Keys($keyname) {
        $keys = $this->redis->KEYS($this->keypre . $keyname);
        return $keys;
    }

    public function zCount($keyname, $start, $end) {
        $i = $this->redis->zCount($this->keypre . $keyname, $start, $end);
        return $i;
    }

    public function hGetAll($keyname) {
        $row = $this->redis->hGetAll($this->keypre . $keyname);
        if ($row === FALSE) {
            $row = array();
        }
        return $row;
    }

    public function gsCache($name, $fn, $ttl = NULL) {
        $key = 'cache_' . $name;
        $sJson = $this->get($key);
        if ($sJson !== FALSE) {
            //已经get到数据了
            $aData = json_decode($sJson, TRUE);
            if ($aData !== NULL) {
                //json解析没有异常
                return $aData;
            }
        }
        //get不到数据，或者json解析出异常了
        $aData = call_user_func($fn);
        $this->set($key, json_encode($aData), $ttl);
        return $aData;
    }

}
