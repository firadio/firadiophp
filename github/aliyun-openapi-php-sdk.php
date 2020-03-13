<?php

include_once (__DIR__) . '/github.com/aliyun/aliyun-openapi-php-sdk/aliyun-php-sdk-core/Config.php';
use Ecs\Request\V20140526 as Ecs;

$regionId = 'cn-huhehaote';
$accessKeyId = '';
$accessSecret = '';
require(__DIR__ . '/aliyun-openapi-php-sdk.secret~');
$iClientProfile = DefaultProfile::getProfile($regionId, $accessKeyId, $accessSecret);
$client = new DefaultAcsClient($iClientProfile);
if (0) {
    $request = new Ecs\DescribeRegionsRequest();
    $request->setMethod("GET");
    $response = $client->getAcsResponse($request);
}
$aTemp = array();
if (0) {
    // 删除实例
    $request = new Ecs\DeleteInstanceRequest();
    //$request->setAction('DeleteInstance');
    $request->setRegionId('cn-zhangjiakou');
    $request->setInstanceId('i-8vbioeatvprz50w4bn48');
    $request->setForce(TRUE);
    $response = $client->getAcsResponse($request);
    $request = null;
    print_r($response);
    exit;
}

if (1) {
    //https://help.aliyun.com/document_detail/63440.html
    //$request = new Ecs\RunInstancesRequest();
    $request = new Ecs\CreateInstanceRequest();
    //$request->setAction('CreateInstance');
    //$request->setImageId("ubuntu_18_04_64_20G_alibase_20190624.vhd");
    /*
    https://ecs-buy-cn-zhangjiakou.aliyun.com/api/ecsImage/describeImages.json?resourceGroupId=&regionId=cn-zhangjiakou&instanceType=ecs.g5.large&isSupportIoOptimized=True&imageOwnerAlias=system&pageSize=100&pageNumber=1&chargeType=PostPaid&includeMarketSystemImage=true&$$namespace=ecs-buy&$$withCredentials=true&$$responseType=json
    https://ecs-buy-cn-zhangjiakou.aliyun.com/api/ecsImage/queryImageByImageId.json?resourceGroupId=&regionId=cn-zhangjiakou&imageIds=centos_7_7_x64_20G_alibase_20200220.vhd&supportIoOptimized=true&chargeType=AfterPay&$$namespace=ecs-buy&$$withCredentials=true&$$responseType=json
    */
    $request->setImageId("centos_7_7_x64_20G_alibase_20200220.vhd");
    //$request->setInstanceType('ecs.xn4.small'); // 1H1G
    $request->setInstanceType('ecs.hfc6.large'); // 2H4G
    $request->setRegionId('cn-zhangjiakou');
    $request->setSecurityGroupId('sg-8vb2mo08dig3bjx8d016');
    $request->setInstanceName('firadio-1');
    $request->setInternetChargeType('PayByTraffic'); // PayByTraffic || PayByBandwidth
    //$request->setAutoRenew('true');
    //$request->setAutoRenewPeriod('1');
    $request->setInternetMaxBandwidthOut(1); // 上传速度
    //$request->setInternetMaxBandwidthIn(100); // 下载速度
    //$request->setHostName('');
    $request->setPassword('Aa1feier'); // 长度为8至30个字符，必须同时包含大小写英文字母、数字和特殊符号中的三类字符。特殊符号可以是：()`~!@#$%^&*-_+=|{}[]:;'<>,.?/
    //$request->setPasswordInherit('true'); // 是否使用镜像预设的密码。使用该参数时，Password参数必须为空，同时您需要确保使用的镜像已经设置了密码。
    $request->setSystemDiskSize(20); // 系统盘大小，单位为GiB。取值范围：20~500
    $request->setSystemDiskCategory('cloud_efficiency'); // cloud_efficiency(高效云盘)
    $request->setInstanceChargeType('PostPaid'); // PrePaid(包年包月) || PostPaid(按量付费)
    $request->setSpotStrategy('SpotAsPriceGo'); // NoSpot(正常按量付费) | SpotWithPriceLimit(设置上限价格) | SpotAsPriceGo(系统自动出价)
    //$request->setSpotPriceLimit(1);
    $response = $client->getAcsResponse($request);
    if (isset($response->InstanceId)) {
        $aTemp['InstanceId'] = ($response->InstanceId);
        print_r($aTemp);
    }
    sleep(2);
}
if (isset($aTemp['InstanceId'])) {
    //AllocatePublicIpAddress
    $request = new Ecs\AllocatePublicIpAddressRequest();
    //$request->setAction('StartInstance');
    $request->setRegionId('cn-zhangjiakou');
    $request->setInstanceId($aTemp['InstanceId']);
    //$request->IpAddress('1.1.1.1');
    //$request->VlanId('100');
    $response = $client->getAcsResponse($request);
    print_r($response);
}
if (isset($aTemp['InstanceId'])) {
    // 启动实例
    $request = new Ecs\StartInstanceRequest();
    //$request->setAction('StartInstance');
    $request->setRegionId('cn-zhangjiakou');
    $request->setInstanceId($aTemp['InstanceId']);
    //$request->InitLocalDisk('false'); // 适用于实例规格族d1、i1或者i2等包含本地盘的实例。
    $response = $client->getAcsResponse($request);
    print_r($response);
}
file_put_contents('aliyun-openapi-php-sdk.json~', json_encode($response));
