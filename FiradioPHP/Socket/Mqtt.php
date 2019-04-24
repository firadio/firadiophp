<?php

namespace FiradioPHP\Socket;

use FiradioPHP\F;
use Bluerhinos\phpMQTT;

class Mqtt {

    private $config = array();
    private $oMqtt = NULL;

    public function __construct($config) {
        $this->config = $config['config'];
        $ClientID = "ClientID" . rand();
        $this->oMqtt = new phpMQTT($this->config['host'], $this->config['port'], $ClientID);
        // $this->connect();
    }

    private function connect() {
        $this->config['username'] = empty($this->config['username']) ? NULL : $this->config['username'];
        $this->config['password'] = empty($this->config['password']) ? NULL : $this->config['password'];
        $connected = $this->oMqtt->connect(true, NULL, $this->config['username'], $this->config['password']);
        if (!$connected) {
            // F::error("Fail or time out");
            return;
        }
    }

    private function close() {
        $this->oMqtt->close();
    }

    public function publish($topic = 'topic', $message = 'message') {
        $this->connect();
        $this->oMqtt->publish($topic, $message);
        $this->close();
    }
}
