<?php

date_default_timezone_set('PRC');

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__);
}
if (!defined('DATA_DIR')) {
    define('DATA_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'data');
}

function loadFiradioPHP() {
    $dir = APP_ROOT . DS . 'FiradioPHP';
    require_once $dir . DS . 'F.php';
    \FiradioPHP\F::scanDirTree($dir, '', function($a) {
        if ($a[1] === '') {
            return;
        }
        $path = implode(DS, $a);
        $pathinfo = pathinfo($path);
        if ($pathinfo['extension'] !== 'php') {
            return;
        }
        require_once($path);
    });
}

function F_initializer() {
    loadFiradioPHP();

    $oConfig = new \FiradioPHP\System\Config(APP_ROOT . DS . 'config');
    return $oConfig;
}

return F_initializer();
