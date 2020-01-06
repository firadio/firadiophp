<?php

/*
 * 缓存
 */

namespace FiradioPHP\System;

use FiradioPHP\F;

class Cli {

    private $filepath;
    private $lockfile_handle;
    public function __construct() {
        echo "[Cli] Start...\r\n";

    }

    public function lock($filepath) {
        $this->filepath = $filepath;
        $this->lockfile_handle = fopen($this->filepath . '.lock', 'w+');
        if (!flock($this->lockfile_handle, LOCK_EX | LOCK_NB)) {
            die("Error locking file!\r\n");
        }
        register_shutdown_function(array($this, 'shutdown'));
    }

    public function shutdown() {
        fclose($this->lockfile_handle);
        if ($this->exist()) {
            unlink($this->filepath . '.lock');
        }
    }

    public function exist() {
        return file_exists($this->filepath . '.lock');
    }

    public function is_end() {
        if (!$this->exist()) {
            echo "[Cli] Ended!";
            return TRUE;
        }
    }

}
