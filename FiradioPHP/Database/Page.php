<?php

/**
 * Description of sql
 *
 * @author asheng
 */

namespace FiradioPHP\Database;
use \Exception;

class Page {

    private $oSql = array();
    private $aPage = array();

    public function __construct($oSql) {
        $this->oSql = $oSql;
        $this->aPage['offset'] = 0;
        $this->aPage['limit'] = 10;
    }

    public function limit($limit = NULL) {
        if (is_numeric($limit)) {
            $this->aPage['limit'] = $limit;
            return;
        }
        if ($limit === NULL) {
            return $this->aPage['limit'];
        }
    }

    public function offset() {
        return $this->aPage['offset'];
    }

    public function next() {
        $this->oSql->limit($this->aPage['limit'], $this->aPage['offset']);
        $this->aPage['offset'] += $this->aPage['limit'];
        return $this->oSql->select();
    }
}
