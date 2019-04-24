<?php

namespace FiradioPHP\Socket;

use FiradioPHP\F;

class Mqtt {

    private $config = array();

    public function __construct($config) {
        $this->config = $config['config'];
    }


}
