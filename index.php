<?php

//error_reporting(0);

use FiradioPHP\F;
use FiradioPHP\System\ConvertCase;

define('DS', DIRECTORY_SEPARATOR);
define('APP_ROOT', __DIR__);
define('DATA_DIR', APP_ROOT . DS . 'data');

require_once __DIR__ . DS . 'vendor' . DS . 'autoload.php';

//初始化F框架，参数是config根目录
F::init(APP_ROOT . DS . 'config');


$oRes = new \FiradioPHP\Routing\Response();
$oRes->fBeginTime = microtime(TRUE);
$oRes->ipaddr = getIpAddr(filter_input_array(INPUT_SERVER)); //2：输入网络IP地址
$oRes->path = filter_input(INPUT_SERVER, 'PATH_INFO'); //3：输入用户请求路径
$oRes->aRequest = $_REQUEST; //4：用户请求数据（包括sessionId）
//通过 F::json_decode 可以自动识别数组格式和字符串的JSON格式，最终输出数组
$aParam = $oRes->aRequest;
if (isset($aParam['param'])) {
    $aParam2 = F::json_decode($aParam['param']);
    $aParam = array_merge($aParam, $aParam2);
}

$sRawContent = '';
if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'POST') {
    $oRes->sRawContent = $sRawContent = file_get_contents('php://input');
}

if (filter_input(INPUT_SERVER, 'HTTP_CONTENT_TYPE') === 'application/x-www-form-urlencoded') {
    if (substr($sRawContent, 0, 1) === '{') {
        $aParam2 = json_decode($sRawContent, true);
        $aParam = array_merge($aParam, $aParam2);
    }
}

$oRes->aParam = $aParam; //4：用户请求参数
$oRes->request = $aParam; //4：输入用户请求数据
$oRes->sessionId = filter_input(INPUT_POST, 'sessionId');
$oRes->APICOOKID = filter_input(INPUT_COOKIE, 'APICOOKID');

F::headerAllowOrigin(filter_input(INPUT_SERVER, 'HTTP_ORIGIN'));

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: ApiAuth, Content-Type');

try {
    $result = F::$aInstances['router']->getResponse($oRes);
    if ($aParam['case'] === 'underline') {
        
    } else {
        $result = ConvertCase::toCamel($result);
    }
    echo json_encode($result);
} catch (Exception $ex) {
    $iCode = $ex->getCode();
    if ($iCode === -1) {
        exit($ex->getMessage());
    }
}

function getIpAddrIgnoreCase($array, $key) {
    if (isset($array[$key])) {
        return $array[$key];
    }
    $key2 = strtolower($key);
    if (isset($array[$key2])) {
        return $array[$key2];
    }
    $key3 = strtoupper($key);
    if (isset($array[$key3])) {
        return $array[$key3];
    }
}

function getIpAddr($server) {
    if (is_object($server)) {
        //swoole传进来的是一个对象
        $header = $server->header;
        $server = $server->server;
        //取得用户的IP地址
        $val = getIpAddrIgnoreCase($header, 'x-real-ip');
        if (!empty($val)) {
            return $val;
        }
    } else {
        //PHP-FPM和workerman还是原有的
        $val = getIpAddrIgnoreCase($server, 'HTTP_X_REAL_IP');
        if (!empty($val)) {
            return $val;
        }
    }
    $val = getIpAddrIgnoreCase($server, 'REMOTE_ADDR');
    if (!empty($val)) {
        return $val;
    }
    return '0.0.0.0';
}
