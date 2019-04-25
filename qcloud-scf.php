<?php

/*
本代码用于腾讯云的《无服务器云函数》SCF

第一步：新建函数
1：到 https://console.cloud.tencent.com/scf 这里【新建】
2：上传代码的时候要设置好【执行方法】为：qcloud-scf.main_handler

第二步：开启“启用响应集成”
1：到 https://console.cloud.tencent.com/apigateway/ 这里找到你的服务
2：选择【API管理】选项卡里面的【通用API】
3：选择你的【云函数】在操作栏里点【编辑】
4：这里有3个步骤，第1个步骤是【前端配置】，这里直接点【下一步】
5：在第2步的【后端配置】这里把【是否启用响应集成】勾选上，然后【下一步】
6：在第3步的【响应结果】这里直接点【完成】
7：发布服务


*/

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

function getUserPath($path) {
    $aPath = explode('/', $path);
    $aNewPath = array();
    foreach ($aPath as $name) {
        // 过滤不对的路径
        if (empty($name)) {
            continue;
        }
        $aNewPath[] = $name;
    }
    // 删除$aNewPath数组中第一个元素
    array_shift($aNewPath);
    // 返回新路径
    return '/' . implode('/', $aNewPath);
}

function Response($statusCode, $headers, $body) {
    $headers['Access-Control-Allow-Origin'] = '*';
    return array(
        'isBase64Encoded' => false,
        'statusCode' => $statusCode,
        'headers' => $headers,
        'body' => $body
    );
}

function main_handler($event, $context) {
    $fBeginTime = microtime(TRUE);
    $oRes = new \FiradioPHP\Routing\Response();
    $oRes->fBeginTime = $fBeginTime; //1：执行的开始时间
    $oRes->ipaddr = $event->requestContext->sourceIp; //2：用户IP地址
    $oRes->path = getUserPath($event->path); //3：用户请求路径
    $aRequest = (array)$event->queryString; //4：用户请求参数
    $sRawContent = $event->body;
    if (is_string($sRawContent) && !empty($sRawContent)) {
         // 5：POST提交内容
        $oRes->sRawContent = $sRawContent;
        $post = array();
        \parse_str($sRawContent, $post);
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
            return Response(200, $oRes->aResponseHeader, $ex->getMessage());
        }
    }
    $result['duration'] = call_user_func(function () use ($fBeginTime) {
        $d = microtime(TRUE) - $fBeginTime; // 计算出执行间隔
        $ms = $d * 1000; // 转换成毫秒
        $ms = intval($ms * 100) / 100; // 保留2位小数
        return $ms . ' ms';
    });
    return Response(200, $oRes->aResponseHeader, json_encode($result));
}