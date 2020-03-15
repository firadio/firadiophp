<?php

function initializer($context) {
    // if you open the initializer feature, please implement the initializer function, as below:
    if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
    if (!defined('APP_ROOT')) define('APP_ROOT', __DIR__);
    //define('DATA_DIR', APP_ROOT . DS . 'data');
    if (!class_exists('FiradioPHP\\F')) {
        $pRequire = __DIR__ . DS . 'vendor' . DS . 'autoload.php';
        if (!file_exists($pRequire)) {
            $pRequire = __DIR__ . DS . 'FiradioPHP' . DS . 'F.php';
        }
        if (FALSE && class_exists('Aliyun\\OTS\\OTSClient')) {
            // 阿里云已经自带OTSClient了
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
        \FiradioPHP\F::autoload();
        \FiradioPHP\F::init(APP_ROOT . DS . 'config');
    }
    // var_dump($context);
}

function handler($request, $context): \RingCentral\Psr7\Response{
    $fBeginTime = microtime(TRUE);
    /*
    $body       = $request->getBody()->getContents();
    $queries    = $request->getQueryParams();
    $method     = $request->getMethod();
    $headers    = $request->getHeaders();
    $path       = $request->getAttribute('path');
    $requestURI = $request->getAttribute('requestURI');
    $clientIP   = $request->getAttribute('clientIP');
    */
    $oRes = new \FiradioPHP\Routing\Response();
    $oRes->fBeginTime = $fBeginTime; //1：执行的开始时间
    $oRes->setParam('IPADDR', $request->getAttribute('clientIP')); //2：用户IP地址
    $oRes->path = $request->getAttribute('path'); //3：用户请求路径
    $aRequest = $request->getQueryParams(); //4：用户请求参数
    $sRawContent = $request->getBody()->getContents();
    if (!empty($sRawContent)) {
         // 5：POST提交内容
        $oRes->setParam('sRawContent', $sRawContent);
        $post = array();
        parse_str($sRawContent, $post);
        if (is_array($post)) {
            foreach ($post as $k => $v) {
                $aRequest[$k] = $v;
            }
        }
    }
    $oRes->aRequest = $aRequest;
    $result = array();
    try {
        $result = \FiradioPHP\F::$aInstances['router']->getResponse($oRes);
    } catch (Exception $ex) {
        $result['code'] = $ex->getCode();
        $result['message'] = $ex->getMessage();
        if ($result['code'] === -1) {
            return new \RingCentral\Psr7\Response(200, $oRes->aResponseHeader, $ex->getMessage());
        }
    }
    $result['duration'] = call_user_func(function () use ($fBeginTime) {
        $d = microtime(TRUE) - $fBeginTime; // 计算出执行间隔
        $ms = $d * 1000; // 转换成毫秒
        $ms = intval($ms * 100) / 100; // 保留2位小数
        return $ms . ' ms';
    });
    return new \RingCentral\Psr7\Response(200, $oRes->aResponseHeader, json_encode($result));
}