<?php

//error_reporting(0);
require __DIR__ . '/F.php';

use FiradioPHP\F;
use FiradioPHP\System\ConvertCase;

function initializer() {
    // if you open the initializer feature, please implement the initializer function, as below:
    if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
    if (!defined('APP_ROOT')) define('APP_ROOT', __DIR__);
    //define('DATA_DIR', APP_ROOT . DS . 'data');
    if (!class_exists('FiradioPHP\\F')) {
        $pRequire = __DIR__ . DS . 'vendor' . DS . 'autoload.php';
        if (!file_exists($pRequire)) {
            $pRequire = __DIR__ . DS . 'F.php';
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
initializer();

$oRes = new \FiradioPHP\Routing\Response();
$oRes->fBeginTime = microtime(TRUE);
$oRes->setParam('IPADDR', getIpAddr(filter_input_array(INPUT_SERVER))); //2：输入网络IP地址
$oRes->path = filter_input(INPUT_SERVER, 'PATH_INFO'); //3：输入用户请求路径
//通过 F::json_decode 可以自动识别数组格式和字符串的JSON格式，最终输出数组
$aParam = $_REQUEST; //4：用户请求数据（包括sessionId）
if (isset($aParam['param'])) {
    $aParam2 = F::json_decode($aParam['param']);
    $aParam = array_merge($aParam, $aParam2);
}

$sRawContent = '';

if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'POST') {
    $sRawContent = file_get_contents('php://input');
    $oRes->setParam('sRawContent', $sRawContent);
}

if (filter_input(INPUT_SERVER, 'HTTP_CONTENT_TYPE') === 'application/x-www-form-urlencoded') {
    if (substr($sRawContent, 0, 1) === '{') {
        $aParam2 = json_decode($sRawContent, true);
        $aParam = array_merge($aParam, $aParam2);
    }
}

$oRes->aRequest = $aParam; //4：用户请求参数
$sessionId = filter_input(INPUT_POST, 'sessionId');
if (empty($sessionId)) {
    $sessionId = filter_input(INPUT_COOKIE, 'sessionId');
}
$oRes->setParam('sessionId', $sessionId);

F::headerAllowOrigin(filter_input(INPUT_SERVER, 'HTTP_ORIGIN'));

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: ApiAuth, Content-Type');

$result = array();
try {
    $result = F::$aInstances['router']->getResponse($oRes);
    $aParam['case'] = isset($aParam['case']) ? $aParam['case'] : 'underline';
    if ($aParam['case'] === 'underline') {
        
    } else {
        $result = ConvertCase::toCamel($result);
    }
} catch (\Exception $ex) {
    $result['code'] = $ex->getCode();
    $result['message'] = $ex->getMessage();
    if ($result['code'] === -1) {
        exit($result['message']);
    }
}
echo json_encode($result);

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
