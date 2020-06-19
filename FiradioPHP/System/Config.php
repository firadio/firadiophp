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
    private $aConfigs = array();
    public $aInstances = array();
    private $user_cache = array();

    public function __get($name) {
        if ($name === 'aClass') {
            return $this->aClass;
        }
        F::error("cant get property-name=$name", 'Error In FiradioPHP\System\Config');
    }

    public function __set($name, $value) {
        F::error("cant set property-name=$name", 'Error In FiradioPHP\System\Config');
    }

    public function __construct($configDir) {
        if (is_array($configDir)) {
            // 可载入多个配置文件夹，后面覆盖前面的配置
            foreach ($configDir as $k => $dir) {
                if (!is_dir($dir)) {
                    F::error('not exist configDir[' . $k . ']');
                    return FALSE;
                }
                $this->loadConfig($dir);
            }
        } else if (is_string($configDir)) {
            if (!is_dir($configDir)) {
                F::error('not exist configDir');
                return FALSE;
            }
            $this->loadConfig1($configDir);
        } else {
            F::error('wrong type in configDir');
            return;
        }
        foreach ($this->aConfigs as $name => $aConfig) {
            $this->loadClass($name, $aConfig);
        }
    }

    private function getDirArrOfDir($dir) {
        $dh = \opendir($dir);
        $arr = array();
        if (!$dh) {
            return $arr;
        }
        while (($file = \readdir($dh)) !== false) {
            if ($file === '..' || $file === '.') {
                // 跳过一些..或.的文件夹
                continue;
            }
            $path = $dir . DS . $file;
            if (!is_dir($path)) {
                // 跳过非文件夹，只要文件夹
                continue;
            }
            $arr[$file] = $path;
        }
        closedir($dh);
        return $arr;
    }

    private function loadConfig1($configDir) {
        $dirs = $this->getDirArrOfDir($configDir);
        if (!isset($dirs['default'])) {
            // 当没有默认文件夹的情况下，就用原来的方式导入一个配置文件夹
            $this->loadConfig2($configDir);
            return;
        }
        // 当有default的默认文件夹时，先导入默认文件夹
        $this->loadConfig2($dirs['default']);
        unset($dirs['default']);
        // 然后导入其他文件夹
        foreach ($dirs as $path) {
            $this->loadConfig2($path);
        }
        // 最后才导入原本的文件夹
        $this->loadConfig2($configDir);
    }

    private function loadConfig2($configDir) {
        $dh = \opendir($configDir);
        if (!$dh) {
            return;
        }
        while (($file = \readdir($dh)) !== false) {
            if ($file === '..' || $file === '.') {
                // 跳过一些..或.的文件夹
                continue;
            }
            $path = $configDir . DS . $file;
            if (!is_file($path)) {
                // 跳过文件夹，只要文件
                continue;
            }
            $matches = array();
            if (preg_match('/^([A-Za-z][0-9a-z_]+)\.php$/', $file, $matches)) {
                $name = $matches[1]; // 配置名称
                $aConfig = include($path); // 配置文件内容
                if (is_callable($aConfig)) {
                    //针对一些return为function的配置需要执行后取得设置，例如db
                    $aConfig = $aConfig();
                }
                if (!isset($this->aConfigs[$name])) {
                    // 同名配置的第一个配置文件
                    $this->aConfigs[$name] = $aConfig;
                    continue;
                }
                // 同名配置的其他配置文件（覆盖掉前面的配置）
                $this->array_merge($this->aConfigs[$name], $aConfig);
            }
        }
        closedir($dh);
    }

    private function array_merge(&$a1, $a2) {
        // 通过递归方式将子数组也放进去
        foreach ($a2 as $k => $v) {
            if (is_array($v)) {
                $this->array_merge($a1[$k], $v);
                continue;
            }
            $a1[$k] = $v;
        }
    }

    public function getInstance($sName, $autoBegin = FALSE) {
        //取得一个新实例，或则已经标记为free的空闲实例
        if (!isset($this->aClass[$sName])) {
            F::error('The sName ' . $sName . ' does not exist in the $this->aClass');
            return;
        }
        $aClassInfo = &$this->aClass[$sName];
        $oInstance = array_pop($aClassInfo['free']);
        if ($oInstance === NULL) {
            F::debug("New [{$sName}]");
            if (0) {
                
            } else if (!empty($aClassInfo['args'])) {
                //如果配置文件里有args的参数
                $oRef = new \ReflectionClass($aClassInfo['class']);
                //把args送到class类的构造函数里实例化
                $oInstance = $oRef->newInstanceArgs($aClassInfo['args']);
            } else {
                //这是飞儿云默认配置，把整个配置送到类的构造函数里实例化
                $oInstance = new $aClassInfo['class']($aClassInfo);
            }
        } else {
            $iFreeCount = count($aClassInfo['free']);
            F::debug("Pop [{$sName}], Remain={$iFreeCount}");
        }
        if ($autoBegin === TRUE) {
            if (method_exists($oInstance, 'inTransaction')) {
                //如果是数据库
                if (!$oInstance->inTransaction()) {
                    //且尚未开始事务处理的话
                    $oInstance->beginTransaction();
                }
            }
        }
        return $oInstance;
    }

    public function freeInstance($sName, $oInstance) {
        //将实例记为free空闲状态
        $aClassInfo = &$this->aClass[$sName];
        if (method_exists($oInstance, '__free')) {
            //如果是数据库，且正在事务处理中的话，就会执行回滚操作
            $oInstance->__free(); //回收再利用
        }
        //最后：把使用过的对象返回free里重复利用
        $iFreeCount = array_push($aClassInfo['free'], $oInstance);
        return $iFreeCount;
    }

    private function loadClass($sName, $aConfig) {
        if (empty($aConfig['class'])) {
            F::error('empty class in config=' . $sName);
            return;
        }
        if (!class_exists($aConfig['class'])) {
            F::error('The class does not exist in the ' . $aConfig['class']);
            return;
        }
        $aConfig['name'] = $sName; // 可供类获取唯一的配置名称
        $aConfig['oConfig'] = $this;
        if (in_array($sName, array('router', 'log'))) {
            $class = $aConfig['class'];
            F::$aInstances[$sName] = new $class($aConfig);
            $this->aInstances[$sName] = F::$aInstances[$sName];
        }
        $aConfig['free'] = array();
        $this->aClass[$sName] = $aConfig;
    }

    public function cache_get($key) {
        if (!isset($this->user_cache[$key])) {
            return false;
        }
        return $this->user_cache[$key];
    }

    public function cache_set($key, $value) {
        $this->user_cache[$key] = $value;
    }

    public function cache_del($key) {
        unset($this->user_cache[$key]);
    }

}
