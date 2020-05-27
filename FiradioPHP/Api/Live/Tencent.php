<?php

namespace FiradioPHP\Api\Live;

use FiradioPHP\F;
use FiradioPHP\Socket\Curl;
use FiradioPHP\Socket\HttpClient;

class Tencent {

    private $aConfig;
    private $aApi = array();
    private $oDb;

    public function __construct($conf) {
        $this->aConfig = $conf;
    }

    public function setDb($oDb) {
        $this->oDb = $oDb;
    }



}
