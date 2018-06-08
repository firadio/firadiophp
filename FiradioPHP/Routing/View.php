<?php

namespace FiradioPHP\Routing;

use FiradioPHP\F;

class View {

    public $config = array();
    public $tplvar = array(); //模板变量
    public $cache_tplarr = array();

    function __construct($config) {
        $this->config = $config;
    }

    public function getResponse($oRes) {
        $tplvar = $this->config['assign'];
        $actionRet = $oRes->response;
        foreach ($actionRet as $key => $val) {
            $tplvar[$key] = $val;
        }
        $this->tplvar = $tplvar;
        $html = $this->readHtml($oRes);
        $html = $this->putInc($html);
        return $this->putVar($html, $tplvar);
    }

    public function readHtml($oRes) {
        $page_path = $this->getpath($oRes); //取得已经处理过的PATH_INFO（还是相对路径）
        $dirname = dirname($page_path);
        $pageFile = $this->config['pre_dir'] . DS . $page_path;
        if ($dirname == DS) {
            return $this->file_get_contents($pageFile);
        }
        $boxFile = $this->config['pre_dir'] . DS . $dirname . '.html';
        if (!$this->is_file($boxFile)) {
            F::end('not find boxFile=' . $boxFile);
            return false;
        }
        $boxHtml = $this->file_get_contents($boxFile);
        $pageHtml = $this->file_get_contents($pageFile);
        $boxHtml = str_replace('{$main}', $pageHtml, $boxHtml);
        return $boxHtml;
    }

    public function putInc($html) {
        $reg = '/<!--\{include[\s]*file[\s]*\=[\s]*\"([0-9A-Za-z\/\.\_]+)\"[\s]*\}-->/i';
        $html = preg_replace_callback($reg, function($matches) {
            $inc_file = $matches[1];
            $path = $this->config['pre_dir'] . '/' . $inc_file;
            if (!$this->is_file($path)) {
                F::end('include文件没有找到' . $path);
                return false;
            }
            return $this->file_get_contents($path);
        }, $html);
        return $html;
    }

    public function putVar($html, $tplvar) {
        /*
          $html = str_replace('{$tpljson}', json_encode($tplvar), $html);
          foreach ($tplvar as $key => $value) {
          $html = str_replace('{$' . $key . '}', $value, $html);
          }
          // */
        if (0) {
            $html = str_replace('{$tpljson}', json_encode($tplvar), $html);
        }
        $html = $this->putVarReg($html, $tplvar);
        return $html;
    }

    public function putVarReg($html) {
        $reg = '/(<!--)?\{\$([a-z][a-z0-9_]+)}(-->)?/i';
        $html = preg_replace_callback($reg, function($matches) {
            if (!array_key_exists($matches[2], $this->tplvar)) {
                //echo 'not find key=' . $matches[2] . "\r\n";
                return '-';
            }
            $ret = $this->tplvar[$matches[2]];
            $ret = $this->putVarReg($ret);
            return $ret;
        }, $html);
        return $html;
    }

    private function getPath($oRes) {
        $path = $this->config['pre_dir'] . DS . $oRes->path;
        if ($this->is_file($path . '/index.html')) {
            return $oRes->path . '/index.html';
        }
        if ($this->is_file($path . '.html')) {
            return $oRes->path . '.html';
        }
        F::end('not find tpl file=' . $oRes->path);
        return false;
    }

    private function is_file($file_path) {
        if ($this->config['cache_enable'] && array_key_exists($file_path, $this->cache_tplarr)) {
            return true;
        }
        return is_file($file_path);
    }

    private function file_get_contents($file_path) {
        if (!$this->config['cache_enable']) {
            //缓存被禁用时
            return file_get_contents($file_path);
        }
        if (array_key_exists($file_path, $this->cache_tplarr)) {
            return $this->cache_tplarr[$file_path];
        }
        return $this->cache_tplarr[$file_path] = file_get_contents($file_path);
    }

}
