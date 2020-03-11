<?php

/*
  https://github.com/swiftmailer/swiftmailer
 */

namespace FiradioPHP\Mail;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

/**
 * Description of Swift
 *
 * @author asheng
 */
class AliyunDm {

    private $mailer;
    private $config;
    private $errorCount = 0;
    private $errorCountMax = 2; //最大失败次数

    public function __construct($config) {
        $this->config = $config;
        $this->init();
    }

    private function init() {
        AlibabaCloud::accessKeyClient($this->config['accessKeyId'], $this->config['accessSecret'])
        ->regionId('cn-hangzhou')
        ->asDefaultClient();
    }

    public function send($ToAddress, $Subject, $TextBody) {
        $this->SingleSendMail($ToAddress, $Subject, $TextBody);
        return 1;
    }

    private function FromAlias() {
        if (!empty($this->config['from'])) {
            return $this->config['from'][$this->config['username']];
        }
    }

    public function SingleSendMail($ToAddress, $Subject, $TextBody) {
        try {
            $result = AlibabaCloud::rpc()
            ->product('Dm')
            // ->scheme('https') // https | http
            ->version('2015-11-23')
            ->action('SingleSendMail')
            ->method('POST')
            ->host('dm.aliyuncs.com')
            ->options([
                'query' => [
                    'RegionId' => "cn-hangzhou",
                    'AccountName' => $this->config['username'],
                    'FromAlias' => $this->config['FromAlias'],
                    'AddressType' => "0",
                    'ReplyToAddress' => "false",
                    'ToAddress' => $ToAddress,
                    'Subject' => $Subject,
                    'TextBody' => $TextBody,
                ],
            ])
            ->request();
            //print_r($result->toArray());
        } catch (ClientException $e) {
            //echo $e->getErrorMessage() . PHP_EOL;
            throw $e;
        } catch (ServerException $e) {
            //echo $e->getErrorMessage() . PHP_EOL;
            throw $e;
        }
    }

}
