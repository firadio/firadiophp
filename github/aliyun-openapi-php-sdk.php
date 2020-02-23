<?php

include_once (__DIR__) . '/github.com/aliyun/aliyun-openapi-php-sdk/aliyun-php-sdk-core/Config.php';
use Ecs\Request\V20140526 as Ecs;

$regionId = 'cn-huhehaote';
$accessKeyId = '';
$accessSecret = '';
require(__DIR__ . '/aliyun-openapi-php-sdk.secret~');
$iClientProfile = DefaultProfile::getProfile($regionId, $accessKeyId, $accessSecret);
$client = new DefaultAcsClient($iClientProfile);

$request = new Ecs\DescribeRegionsRequest();
$request->setMethod("GET");
$response = $client->getAcsResponse($request);
file_put_contents('aliyun-openapi-php-sdk.json~', json_encode($response));
