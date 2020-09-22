<?php

namespace FiradioPHP\Database;

class SqlQueue {

    public $oDb = null;
    public $sTable = null; // 表名
    public $sTableAlias = 't1'; //sTable的别名
    public $sJoinTable = NULL;
    public $sJoinField = NULL;
    public $sFieldId = 'id'; // ID列（主键）
    public $sFieldCreated = 'created'; // 创建时间
    public $sFieldUpdated = 'updated'; // 最后更新时间
    public $sFieldState = 'do_state'; // 状态列, 0为未处理队列（需要做索引）
    public $sFieldSeq = 'do_seq'; // 序号列，每次处理+1（需要做索引）
    public $mWhere = array();
    public $aWhere = array();
    private $sCurrentId = null;

    public function __construct() {
        
    }

    public function getOne() {
        $mWhere = $this->mWhere;
        $mWhere['sFieldState'] = 0;
        $oSql = $this->oDb->sql();
        $oSql->table($this->sTable);
        $aField = array($this->sFieldId);
        $aField[] = $this->sFieldSeq;
        // 首先获取下一条需要处理的ID号
        $this->oDb->begin();
        $aWhere = array();
        $aWhere[] = '`' . $this->sFieldState . '`=:sFieldState';
        foreach ($this->aWhere as $sWhereOne) {
            $aWhere[] = $sWhereOne;
        }
        $sWhereAll = implode(' AND ', $aWhere);
        $aRow1 = $oSql->where($mWhere, $sWhereAll)->order($this->sFieldSeq)->field(implode(',', $aField))->find();
        if (empty($aRow1)) {
            //没找到就结束
            $this->oDb->rollback();
            return;
        }
        // 找到了就通过这个ID号来自增Seq
        $aWhereId = array($this->sFieldId => $aRow1[$this->sFieldId]);
        $aSave = array();
        $aSave[$this->sFieldSeq] = $aRow1[$this->sFieldSeq] + 1;
        $oSql->where($aWhereId)->save($aSave);
        $this->oDb->commit();
        //完成自增Seq以后再次找到这个ID
        $this->oDb->begin();
        if ($this->sTableAlias && $this->sJoinTable && $this->sJoinField) {
            $oSql->table($this->sTable . ' ' . $this->sTableAlias . ' LEFT JOIN ' . $this->sJoinTable);
            $oSql->where(array($this->sTableAlias . '.' . $this->sFieldId => $aRow1[$this->sFieldId]));
            $aField = array($this->sJoinField);
            $aField[] = $this->sTableAlias . '.' . $this->sFieldId;
            $aField[] = $this->sTableAlias . '.' . $this->sFieldState;
            $aField[] = $this->sTableAlias . '.' . $this->sFieldSeq;
            $oSql->field(implode(',', $aField));
        } else {
            $oSql->where($aWhereId);
            $oSql->field('*');
        }
        $oSql->lock();
        $aRow2 = $oSql->find();
        if (!empty($aRow2[$this->sFieldState])) {
            //非0就是处理过了，必须跳过
            $this->oDb->rollback();
            return;
        }
        if (intval($aRow2[$this->sFieldSeq]) !== $aSave[$this->sFieldSeq]) {
            // Seq不同说明这条记录已经被其他线程处理过了
            $this->oDb->rollback();
            return 'R';
        }
        $this->sCurrentId = $aRow2[$this->sFieldId];
        unset($aRow2[$this->sFieldState]);
        unset($aRow2[$this->sFieldSeq]);
        return $aRow2;
    }

    public function setState($iState) {
        $aSave = array();
        $aSave[$this->sFieldState] = $iState;
        $this->save($aSave);
    }

    public function save($aSave) {
        $oSql = $this->oDb->sql()->table($this->sTable);
        $oSql->where($this->sFieldId, $this->sCurrentId)->save($aSave);
        $this->oDb->commit();
    }

    public function rollback() {
        $this->oDb->rollback();
    }

}
