<?php

namespace FiradioPHP\Database;

use \MongoDB\Driver\Manager;
use \MongoDB\Driver\BulkWrite;
use \MongoDB\Driver\WriteConcern;

class MongoDB {


    private $oManager;
    private $mConfig;

    public function __construct($oConfig) {
        $this->mConfig = $oConfig['config'];
        if (empty($this->mConfig['timeout'])) {
            $this->mConfig['timeout'] = 10;
        }
        $mConfig = $this->mConfig;
        $sUrl = "mongodb://{$mConfig['host']}:{$mConfig['port']}";
        $this->oManager = new Manager($sUrl);
    }

    public function remove($sTable, $mFilter) {
        $oBulk = new BulkWrite;
        $mOption = array();
        $oBulk->delete($mFilter, $mOption);
        $oWriteConcern = new WriteConcern(WriteConcern::MAJORITY, $this->mConfig['timeout']);
        $sNamespace = $this->mConfig['dbname'] . '.' . $sTable;
        $oResult = $this->oManager->executeBulkWrite($sNamespace, $oBulk, $oWriteConcern);
        return $oResult->getDeletedCount();
    }


}
