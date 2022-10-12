<?php

/*
 * 缓存
 */

namespace FiradioPHP\System;

use FiradioPHP\F;

class Cli {

    private $filepath;
    private $lockfile_handle;
    private $rootdir;
    public function __construct($rootdir = __DIR__ . '/lock') {
        echo "[Cli] Start...\r\n";
        $this->rootdir = $rootdir;
        if (!file_exists($this->rootdir)) {
            mkdir($this->rootdir);
        }
    }

    public function getUnLockIds() {
        $aUnLockIds = array();
        if ($handle = opendir($this->rootdir)) {
            while (false !== ($entry = readdir($handle))) {
                $filepath = $this->rootdir . '/' . $entry;
                if (is_file($filepath)) {
                    $lockfile_handle = fopen($filepath, 'w+');
                    if (flock($lockfile_handle, LOCK_EX | LOCK_NB)) {
                        fclose($lockfile_handle);
                        $aUnLockIds[] = pathinfo($filepath, PATHINFO_FILENAME);
                    }
                }
            }
        }
        return $aUnLockIds;
    }

    public function delLockId($lock_id) {
        $filepath = $this->rootdir . '/' . $lock_id . '.lock';
        $lockfile_handle = fopen($filepath, 'w+');
        if (flock($lockfile_handle, LOCK_EX | LOCK_NB)) {
            unlink($filepath);
        }
    }

    public function newLockId() {
        //生成新的锁ID
        $lock_id = mt_rand(100000, 999999);
        $this->lock($this->rootdir . '/' . $lock_id);
        return $lock_id;
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
            echo "[Cli] Ended!\r\n";
            return TRUE;
        }
    }

}
