<?php

initializer();

function initializer() {
    if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
    if (!defined('APP_ROOT')) define('APP_ROOT', __DIR__);
    if (!class_exists('FiradioPHP\\F')) {
        $pRequire = __DIR__ . DS . 'vendor' . DS . 'autoload.php';
        if (!file_exists($pRequire)) {
            $pRequire = __DIR__ . DS . 'FiradioPHP' . DS . 'F.php';
        }
        if (!file_exists($pRequire)) {
            die('not find file FiradioPHP');
            return;
        }
        require_once $pRequire;
    }
    if (!class_exists('FiradioPHP\\F')) {
        die('not load class FiradioPHP');
        return;
    }
    if (empty(\FiradioPHP\F::$oConfig)) {
        // 初始化F框架，参数是config根目录
        \FiradioPHP\F::init(APP_ROOT . DS . 'config');
    }
}

function main_handler($event, $context) {
    $fBeginTime = microtime(TRUE);
    $oRes = new \FiradioPHP\Routing\Response();
    $oRes->fBeginTime = $fBeginTime; //1：执行的开始时间
    $oRes->ipaddr = $event['requestContext']['sourceIp']; //2：用户IP地址
    $oRes->path = $event['path']; //3：用户请求路径
    $aRequest = $event['queryString']; //4：用户请求参数
    $sRawContent = $event['body'];
    if (!empty($sRawContent)) {
         // 5：POST提交内容
        $oRes->sRawContent = $sRawContent;
        $post = array();
        parse_str($sRawContent, $post);
        if (is_array($post)) {
            $oRes->aParam = $post;
            foreach ($post as $k => $v) {
                $aRequest[$k] = $v;
            }
        }
    }
    $oRes->aRequest = $aRequest;
    $result = $oRes->aResponse;
    try {
        $result = \FiradioPHP\F::$aInstances['router']->getResponse($oRes);
    } catch (Exception $ex) {
        $result['code'] = $ex->getCode();
        $result['message'] = $ex->getMessage();
        if ($iCode === -1) {
            return $ex->getMessage();
        }
    }
    $result['duration'] = call_user_func(function () use ($fBeginTime) {
        $d = microtime(TRUE) - $fBeginTime; // 计算出执行间隔
        $ms = $d * 1000; // 转换成毫秒
        $ms = intval($ms * 100) / 100; // 保留2位小数
        return $ms . ' ms';
    });
    return json_encode($result);
}