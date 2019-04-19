<?php

namespace FiradioPHP\Api\Aliyun;

use FiradioPHP\F;
use Dm\Request\V20151123\SingleSendMailRequest;

/**
 * https://github.com/rjyxz/aliyun-php-sdk-dm
 */
class Dm {

    private $aConfig;

    public function __construct($conf = array()) {
        $config = $conf['config'];
        $this->aConfig = $config;
    }

    function sendMail($ToAddress, $HtmlBody, $Subject = ''){
        $iClientProfile = \DefaultProfile::getProfile($this->aConfig['Region'], $this->aConfig['AccessKeyID'], $this->aConfig['AccessKeySecret']);
        $client = new \DefaultAcsClient($iClientProfile);
        $request = new SingleSendMailRequest();
        $request->setAccountName($this->aConfig['AccountName']); // 控制台创建的发信地址
        $request->setFromAlias($this->aConfig['FromAlias']); // 发信人昵称
        $request->setAddressType($this->aConfig['AddressType']); // 填写1
        $request->setTagName($this->aConfig['TagName']); // 控制台创建的标签
        $request->setReplyToAddress($this->aConfig['ReplyToAddress']); // 填写true
        $request->setToAddress($ToAddress); // 目标地址
        $request->setSubject($Subject); // 邮件主题
        $request->setHtmlBody($HtmlBody); // 邮件正文
        try {
            $response = $client->getAcsResponse($request);
            print_r($response);
        }
        catch (\ClientException  $e) {
            $code = $e->getErrorCode();
            if ($code === 'Forbidden') {
                F::error('指定的RAM权限不足');
            }
            var_dump($e->getErrorMessage());
        }
    }

}
