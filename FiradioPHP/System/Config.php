<?php

namespace FiradioPHP\System;

use FiradioPHP\F;

/**
 * Description of config
 *
 * @author asheng
 */
class Config {

    private $aClass = array();

    public function __construct($configDir) {
        $dh = \opendir($configDir);
        if (!$dh) {
            return;
        }
        while (($file = \readdir($dh)) !== false) {
            if ($file === '..' || $file === '.') {
                continue;
            }
            $path = $configDir . DS . $file;
            if (!is_file($path)) {
                //跳过一些..或.的文件夹，只要文件
                continue;
            }
            $matches = array();
            if (preg_match('/^([A-Za-z][0-9a-z_]+)\.php$/', $file, $matches)) {
                $config = include($path);
                $this->loadClass($matches[1], $config);
            }
        }
        closedir($dh);
    }

    public function getInstance($sName) {
        //取得一个新实例，或则已经标记为free的空闲实例
        if (!isset($this->aClass[$sName])) {
            F::error('The sName ' . $sName . ' does not exist in the $this->aClass');
            return;
        }
        $aClassInfo = &$this->aClass[$sName];
        $oInstance = array_pop($aClassInfo['free']);
        if ($oInstance === NULL) {
            F::debug('new ' . $sName);
            $oInstance = new $aClassInfo['class']($aClassInfo['config']);
        }
        if (preg_match('/^db[0-9]+$/', $sName) || preg_match('/^db_[a-z]+$/', $sName)
        ) {
            if (!$oInstance->inTransaction()) {
                $iFreeCount = count($aClassInfo['free']);
                //F::info($sName . '->beginTransaction FreeCount=' . $iFreeCount);
                $oInstance->beginTransaction();
            }
        }
        return $oInstance;
    }

    public function freeInstance($sName, $oInstance) {
        //将实例记为free空闲状态
        $aClassInfo = &$this->aClass[$sName];
        $iFreeCount = array_push($aClassInfo['free'], $oInstance);
        if (preg_match('/^db[0-9]+$/', $sName)) {
            if ($oInstance->inTransaction()) {
                //F::info($sName . '->rollback FreeCount=' . $iFreeCount);
                $oInstance->rollback();
            }
        }
        return $iFreeCount;
    }

    private function loadClass($sName, $aConfig) {
        if (is_callable($aConfig)) {
            //针对一些return为function的配置需要执行后取得设置，例如db
            $aConfig = $aConfig();
        }
        if (!isset($aConfig['class'])) {
            F::error('The key "class" does not exist in the config.');
            return;
        }
        if (!class_exists($aConfig['class'])) {
            F::error('The class does not exist in the ' . $aConfig['class']);
            return;
        }
        if (in_array($sName, array('router', 'log'))) {
            $class = $aConfig['class'];
            F::$aInstances[$sName] = new $class($aConfig);
        }
        $aClassInfo = array();
        $aClassInfo['class'] = $aConfig['class'];
        $aClassInfo['config'] = $aConfig;
        $aClassInfo['free'] = array();
        $this->aClass[$sName] = $aClassInfo;
    }

}
