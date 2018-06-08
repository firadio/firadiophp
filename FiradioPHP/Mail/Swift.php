<?php

/*
  https://github.com/swiftmailer/swiftmailer
 */

namespace FiradioPHP\Mail;

use \Swift_SmtpTransport;
use \Swift_Mailer;
use \Swift_Message;
use \Exception;

/**
 * Description of Swift
 *
 * @author asheng
 */
class Swift {

    private $mailer;
    private $config;
    private $errorCount = 0;
    private $errorCountMax = 2; //最大失败次数

    public function __construct($config) {
        $this->config = $config;
        $this->init();
    }

    private function init() {
        // Create the Transport
        $transport = Swift_SmtpTransport::newInstance();
        $transport->setHost($this->config['host']);
        $transport->setPort($this->config['port']);
        $transport->setEncryption($this->config['encryption']);
        $transport->setUsername($this->config['username']);
        $transport->setPassword($this->config['password']);
        // Create the Mailer using your created Transport
        $this->mailer = Swift_Mailer::newInstance($transport);
    }

    public function send($to, $subject, $body) {
        // Create the message
        $message = Swift_Message::newInstance();
        // Give the message a subject
        $message->setSubject($subject);
        // Set the From address with an associative array
        $message->setFrom($this->config['from']);
        // Set the To addresses with an associative array
        $setTo = array();
        if (is_array($to)) {
            $setTo = $to;
        } else if (is_string($to)) {
            $setTo[] = $to;
        }
        $message->setTo($setTo);
        // Give it a body
        $message->setBody($body);
        return $this->sendByMessage($message);
    }

    public function sendByMessage($message) {
        try {
            return $this->mailer->send($message);
        } catch (Exception $ex) {
            if ($this->errorCount >= $this->errorCountMax) {
                $this->errorCount = 0; //重置错误计数
                throw $ex;
            }
            $this->errorCount++;
            $this->mailer->getTransport()->stop();
            return $this->sendByMessage($message);
        }
    }

}
