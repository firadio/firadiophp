<?php

function initializer($context) {
    // if you open the initializer feature, please implement the initializer function, as below:
    define('DS', DIRECTORY_SEPARATOR);
    define('APP_ROOT', __DIR__);
    //define('DATA_DIR', APP_ROOT . DS . 'data');
    require_once __DIR__ . DS . 'vendor' . DS . 'autoload.php';
    // 初始化F框架，参数是config根目录
    \FiradioPHP\F::init(APP_ROOT . DS . 'config');
    var_dump($context);
}

function handler($request, $context): \RingCentral\Psr7\Response{
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
    $oRes->fBeginTime = microtime(TRUE); //1：执行的开始时间
    $oRes->ipaddr = $request->getAttribute('clientIP'); //2：用户IP地址
    $oRes->path = $request->getAttribute('path'); //3：用户请求路径
    $oRes->aRequest = $request->getQueryParams(); //4：用户请求参数
    //$oRes->sRawContent = $body; // 5：提交内容
    $result = '';
    try {
        $result = \FiradioPHP\F::$aInstances['router']->getResponse($oRes);
        $result = json_encode($result);
    } catch (Exception $ex) {
        $iCode = $ex->getCode();
        if ($iCode === -1) {
            $result = $ex->getMessage();
        }
    }
    return new \RingCentral\Psr7\Response(
        200,
        array(
            'custom_header1' => 'v1',
            'custom_header2' => ['v2', 'v3'],
        ),
        $result
    );
}