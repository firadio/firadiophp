<?php

/*
 * 缓存
 */

namespace FiradioPHP\System;

use FiradioPHP\F;

class Cache {

    private $aConfig = array();
    private $sPrefix = '';

    public function __construct($conf) {
        $this->aConfig = $conf;
    }

    public function setPrefix($prefix) {
        $this->sPrefix = $prefix;
    }

    private function filename($name) {
        $dir = $this->aConfig['pre_dir'];
        if (!file_exists($dir)) {
            mkdir($dir);
        }
        $name = $this->sPrefix . $name;
        $file = $dir . DS . $this->sPrefix . $name . '.txt';
        return $file;
    }

    public function set($name, $value) {
        $file = $this->filename($name);
        file_put_contents($file, $value);
    }

    public function get($name) {
        $file = $this->filename($name);
        if (!file_exists($file)) {
            return;
        }
        return file_get_contents($file);
    }

}
