<?php

namespace FiradioPHP\Database;

class MysqlQueue {

    public $oDb = null;
    public $sTable = null; // 表名
    public $sFieldId = 'id'; // ID列（主键）
    public $sFieldCreated = 'created'; // 创建时间
    public $sFieldUpdated = 'updated'; // 最后更新时间
    public $sFieldState = null; // 状态列, 0为未处理队列（需要做索引）
    public $sFieldSeq = null; // 序号列，每次处理+1（需要做索引）
    private $sCurrentId = null;

    public function __construct() {
        
    }

    public function getOne() {
        $aWhere = array();
        $aWhere[$this->sFieldState] = 0;
        $oSql = $this->oDb->sql()->table($this->sTable);
        $aField = array($this->sFieldId);
        $aField[] = $this->sFieldSeq;
        // 首先获取下一条需要处理的ID号
        $aRow1 = $oSql->where($aWhere)->order($this->sFieldSeq)->field(implode(',', $aField))->find();
        if (empty($aRow1)) {
            return;
        }
        $this->oDb->begin();
        // 通过这个ID号来锁行
        $aRow2 = $oSql->where($this->sFieldId, $aRow1[$this->sFieldId])->field('*')->lock()->find();
        if ($aRow2[$this->sFieldSeq] !== $aRow1[$this->sFieldSeq]) {
            // Seq不同说明这条记录已经被其他线程处理过了
            $this->oDb->rollback();
            return;
        }
        $this->sCurrentId = $aRow2[$this->sFieldId];
        $aSave = array();
        $aSave[$this->sFieldSeq] = $aRow1[$this->sFieldSeq] + 1;
        $oSql->where($this->sFieldId, $aRow1[$this->sFieldId])->save($aSave);
        $this->oDb->commit();
        unset($aRow2[$this->sFieldId]);
        unset($aRow2[$this->sFieldCreated]);
        unset($aRow2[$this->sFieldUpdated]);
        unset($aRow2[$this->sFieldState]);
        unset($aRow2[$this->sFieldSeq]);
        return $aRow2;
    }

    public function setState($iState) {
        $this->oDb->begin();
        $aSave = array();
        $aSave[$this->sFieldState] = $iState;
        $oSql = $this->oDb->sql()->table($this->sTable);
        $oSql->where($this->sFieldId, $this->sCurrentId)->save($aSave);
        $this->oDb->commit();
    }

}
