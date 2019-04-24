<?php

return array(
    'class' => '\FiradioPHP\Api\Aliyun\Dm',
    'config' => array(
        'Region' => 'cn-hangzhou', // 地域
        'AccessKeyID' => '',
        'AccessKeySecret' => '',
        'AccountName' => 'yun@dm.firadio.net', // 控制台创建的发信地址
        'FromAlias' => '飞儿云平台', // 发信人昵称
        'AddressType' => 1,
        'TagName' => '', // 控制台创建的标签
        'ReplyToAddress' => 'true',
    )
);
