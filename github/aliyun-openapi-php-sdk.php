<?php

include_once (__DIR__) . '/github.com/aliyun/aliyun-openapi-php-sdk/aliyun-php-sdk-core/Config.php';
use Ecs\Request\V20140526 as Ecs;

$iClientProfile = DefaultProfile::getProfile("cn-hangzhou", "<your accessKey>", "<your accessSecret>");
$client = new DefaultAcsClient($iClientProfile);

$request = new Ecs\DescribeRegionsRequest();
$request->setMethod("GET");
$response = $client->getAcsResponse($request);
print_r($response);
