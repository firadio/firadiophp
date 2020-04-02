<?php

/**
 * Description of sql
 *
 * @author asheng
 */

namespace FiradioPHP\Database;
use \Exception;

class Sql {

    private $aSql = array();
    private $aPage = array();
    public $aConfig = array();
    public $link;

    public function __construct($link) {
        $this->link = $link;
        $this->aSql['ignore'] = FALSE;
        $this->aSql['where'] = array();
        $this->aSql['paramData'] = array();
        $this->aConfig['check_field_name'] = TRUE;
    }

    public function field($field) {
        $this->aSql['field'] = $field;
        return $this;
    }

    public function table($table) {
        $this->aSql['table_raw'] = $table;
        if (strpos($table, '{tablepre}') !== FALSE) {
            //有{tablepre}的就替换好
            $this->aSql['table'] = str_replace('{tablepre}', $this->link->tablepre, $table);
            return $this;
        }
        if (preg_match('/^[A-Za-z0-9_]+(\s[a-z]+)?$/u', $table)) {
            //如果没有带上前缀标记就自动加上
            $this->aSql['table'] = $this->link->tablepre . $table;
            return $this;
        }
        $this->aSql['table'] = $table;
        return $this;
    }

    public function tableField($tableField) {
        $this->aSql['tableField'] = $tableField;
        $convert_field = function ($tableName, $tableAlias, $sFields) {
            $aField = explode(',', $sFields);
            $sRet = '';
            foreach ($aField as $iKey => $sField) {
                if ($iKey > 0) {
                    $sRet .= ',';
                }
                $sRet .= '`' . $tableAlias . '`.`' . $sField . '`';
                $i = strrpos($sField, '_');
                if ($i === false) {
                    $sRet .= ' ' . $tableName . '_' . $sField;
                    continue;
                }
                $sRet .= ' ' . $sField;
            }
            return $sRet;
        };
        foreach ($tableField as $iKey => $aRow) {
            $sTable = $this->link->tablepre . $aRow[0];
            $sFields = $aRow[1];
            $sLeftJoinOn = isset($aRow[2]) ? $aRow[2] : '';
            $aTable = explode(' ', $aRow[0]);
            $tableName = $aTable[0];
            $tableAlias = isset($aTable[1]) ? $aTable[1] : $aTable[0];
            if ($iKey === 0) {
                $this->aSql['field'] = $convert_field($tableName, $tableAlias, $sFields);
                $this->aSql['table'] = $sTable;
                continue;
            }
            $this->aSql['field'] .= ',' . $convert_field($tableName, $tableAlias, $sFields);
            $this->aSql['table'] .= ' LEFT JOIN ' . $sTable;
            if ($sLeftJoinOn) {
                $this->aSql['table'] .= ' ON ' . $sLeftJoinOn;
            }
        }
        return $this;
    }

    private function getWhereKey(&$key) {
        $table = NULL;
        $field = $key;
        $posi = strpos($key, '.');
        if ($posi !== FALSE) {
            $table = substr($key, 0, $posi);
            if (!preg_match('/^[a-z][0-9a-z_]{0,10}$/i', $table)) {
                return FALSE;
            }
            // 在包含小数点的时候，取小数点后的为字段
            $field = substr($key, $posi + 1);
            $key = $table . '_' . $field;
        }
        if (!preg_match('/^[a-z][0-9a-z_]{0,19}$/i', $field)) {
            //必须字母开头，可以包含字母和数字还有下划线
            return FALSE;
        }
        $str = '`' . $field . '`';
        if ($table) {
            $str = '`' . $table . '`.' . $str;
        }
        return $str;
    }

    private function buildSqlWhere() {
        $where = $this->aSql['where'];
        $where_keys = array();
        foreach ($where as $key => $val) {
            $whereKey = $this->getWhereKey($key);
            if ($whereKey === FALSE) continue;
            if ($val === NULL) {
                $whereKey = 'ISNULL(' . $whereKey . ')';
                $where_keys[] = $whereKey;
                continue;
            }
            if (is_array($val)) {
                if ($val[0] === 'FIND_IN_SET') {
                    $where_keys[] = ' FIND_IN_SET(:' . $key . ',' . $whereKey . ')';
                    $this->aSql['paramData'][$key] = $val[1];
                    continue;
                }
                $fields = array();
                foreach ($val as $vk => $vv) {
                    $fields[] = ':' . $key . '_' . $vk;
                    $this->aSql['paramData'][$key . '_' . $vk] = $vv;
                }
                $where_keys[] = $whereKey . ' IN(' . implode(',', $fields) . ')';
                continue;
            }
            $whereKey .= '=:' . $key;
            $where_keys[] = $whereKey;
            $this->aSql['paramData'][$key] = $val;
        }
        $this->aSql['sql_where'] = implode(' AND ', $where_keys);
    }

    private function checkConditionName($fieldName) {
        if (!is_string($fieldName)) return FALSE;
        if (!preg_match('/^[a-z][0-9a-z_.]{0,50}$/i', $fieldName)) {
            return FALSE;
        }
        return TRUE;
    }

    public function where($where, $sql = NULL) {
        if ($this->checkConditionName($where)) {
            $this->aSql['where'][$where] = $sql;
            $this->buildSqlWhere();
            return $this;
        }
        if (is_array($where) && is_string($sql) && !empty($sql)) {
            $this->aSql['paramData'] = $where;
            $this->aSql['sql_where'] = $sql;
            return $this;
        }
        if (is_array($where)) {
            $this->aSql['where'] = $where;
            $this->buildSqlWhere();
            return $this;
        }
        $this->aSql['sql_where'] = $where;
        return $this;
    }

    public function where_sql($sql, $data) {
        $this->aSql['sql_where'] = $sql;
        $this->data($data);
        return $this;
    }

    public function param($param) {
        $this->aSql['paramData'] = $param;
        return $this;
    }

    public function data($data) {
        if (!is_array($data)) {
            $this->error('必须传入必须是数组');
            return;
        }
        $this->aSql['paramField'] = array();
        if (!isset($this->aSql['paramData'])) {
            $this->aSql['paramData'] = array();
        }
        $mysqlFun = array();
        $mysqlFun['CURRENT_TIMESTAMP()'] = 'CURRENT_TIMESTAMP()';
        $mysqlFun['CURRENT_DATE()'] = 'CURRENT_DATE()';
        $mysqlFun['FLOOR(UNIX_TIMESTAMP(NOW())/3600)'] = 'FLOOR(UNIX_TIMESTAMP(NOW())/3600)';
        foreach ($data as $key => $val) {
            if ($this->aConfig['check_field_name'] && !preg_match('/^[a-z][0-9a-z_]{1,19}$/i', $key)) {
                //必须字母开头，可以包含字母和数字还有下划线
                continue;
            }
            if ($val === NULL) {
                $this->aSql['paramField'][$key] = 'NULL';
                continue;
            }
            if (isset($mysqlFun[$val])) {
                $this->aSql['paramField'][$key] = $mysqlFun[$val];
                continue;
            }
            $paramName = 'crc32_' . crc32($key);
            $this->aSql['paramField'][$key] = ':' . $paramName;
            $this->aSql['paramData'][$paramName] = $val;
        }
        return $this;
    }

    public function group($group_fields) {
        $this->aSql['group_fields'] = $group_fields;
        return $this;
    }

    public function order($order_fields, $order_orient = NULL) {
        $this->aSql['order_fields'] = $order_fields;
        if ($order_orient !== NULL) {
            $this->aSql['order_orient'] = $order_orient;
        }
        return $this;
    }

    public function desc($order_fields) {
        $this->order($order_fields, 'DESC');
        return $this;
    }

    public function limit($limit, $offset = NULL) {
        $this->aSql['limit'] = $limit;
        if ($offset !== NULL) {
            $this->aSql['offset'] = $offset;
        }
        return $this;
    }

    public function offset($offset) {
        $this->aSql['offset'] = $offset;
        return $this;
    }

    public function page($page) {
        $this->aPage['page'] = intval($page);
        if (isset($this->aPage['page_max']) && $this->aPage['page'] > $this->aPage['page_max']) {
            $this->aPage['page'] = $this->aPage['page_max'];
        }
        if ($this->aPage['page'] < 1) {
            $this->aPage['page'] = 1;
        }
        $this->aSql['offset'] = $this->aSql['limit'] * ($this->aPage['page'] - 1);
        return $this;
    }

    public function size($size) {
        $this->aPage['size'] = intval($size);
        if ($this->aPage['size'] < 1) {
            $this->aPage['size'] = 1;
        }
        if ($this->aPage['size'] > 1000) {
            $this->aPage['size'] = 1000;
        }
        $this->aSql['limit'] = $this->aPage['size'];
        if (isset($this->aPage['count'])) {
            $this->aPage['page_max'] = ceil($this->aPage['count'] / $this->aPage['size']);
        }
        if (isset($this->aPage['page'])) {
            $this->page($this->aPage['page']);
        }
        return $this;
    }

    public function count2() {
        $sql = 'SELECT';
        $sql .= ' COUNT(*)';
        $sql .= ' FROM ' . $this->aSql['table'];
        if (isset($this->aSql['sql_where']) && !empty($this->aSql['sql_where'])) {
            $sql .= ' WHERE ' . $this->aSql['sql_where'];
        }
        $sth = $this->getSth($sql);
        $row = $sth->fetch(\PDO::FETCH_NUM);
        $this->aPage['count'] = intval($row[0]);
        if (isset($this->aPage['size'])) {
            $this->size($this->aPage['size']);
        }
        return $this->aPage['count'];
    }

    public function lock() {
        $this->aSql['lock'] = true;
        return $this;
    }

    public function buildSqlSelect($isFetchOne = FALSE) {
        $sql = 'SELECT';
        if (isset($this->aSql['field'])) {
            $sql .= ' ' . $this->aSql['field'];
        } else {
            $sql .= ' *';
        }
        $sql .= ' FROM ' . $this->aSql['table'];
        if (isset($this->aSql['sql_where']) && !empty($this->aSql['sql_where'])) {
            $sql .= ' WHERE ' . $this->aSql['sql_where'];
        }
        if (isset($this->aSql['group_fields'])) {
            $sql .= ' GROUP BY ' . $this->aSql['group_fields'];
        }
        if (isset($this->aSql['order_fields'])) {
            $sql .= ' ORDER BY ' . $this->aSql['order_fields'];
            if (isset($this->aSql['order_orient'])) {
                $sql .= ' ' . $this->aSql['order_orient'];
            }
        }
        if ($isFetchOne) {
            $sql .= ' LIMIT 1';
        } else
        if (isset($this->aSql['limit'])) {
            $sql .= ' LIMIT ' . $this->aSql['limit'];
            if (isset($this->aSql['offset'])) {
                $sql .= ' OFFSET ' . $this->aSql['offset'];
            }
        }
        if (!empty($this->aSql['lock'])) {
            $sql .= ' FOR UPDATE';
        }
        return $sql;
    }

    public function buildSqlUpdate($data = NULL) {
        if (!empty($data)) {
            $this->data($data);
        }
        $sSets = array();
        foreach ($this->aSql['paramField'] as $field => $val) {
            $sSets[] = '`' . $field . '`=' . $val;
        }
        $sql = 'UPDATE ' . $this->aSql['table'] . ' SET ' . implode(',', $sSets);
        if (isset($this->aSql['sql_where']) && !empty($this->aSql['sql_where'])) {
            $sql .= ' WHERE ' . $this->aSql['sql_where'];
        } else {
            $this->error('已禁止无条件的更新语句');
        }
        return $sql;
    }

    public function buildSqlInsert() {
        $fields = $values = array();
        foreach ($this->aSql['paramField'] as $field => $val) {
            $fields[] = '`' . $field . '`';
            $values[] = $val;
        }
        $sql = 'INSERT';
        if ($this->aSql['ignore']) $sql .= ' IGNORE';
        $sql .= ' ' . $this->aSql['table'] . '(' . implode(',', $fields) . ')VALUES(' . implode(',', $values) . ')';
        return $sql;
    }

    public function buildSqlInsertWhenNotExists() {
        $fields = $values = array();
        foreach ($this->aSql['paramField'] as $field => $val) {
            $fields[] = '`' . $field . '`';
            $values[] = $val;
        }
        $sql = 'INSERT INTO ' . $this->aSql['table'] . '(' . implode(',', $fields) . ')';
        $sql .= ' SELECT ' . implode(',', $values) . ' FROM dual WHERE NOT EXISTS';
        $sql .= '(SELECT 1 FROM ' . $this->aSql['table'] . ' WHERE ' . $this->aSql['sql_where'] . ')';
        return $sql;
    }

    public function buildSqlDelete() {
        $sql = 'DELETE FROM ' . $this->aSql['table'];
        if (isset($this->aSql['sql_where']) && !empty($this->aSql['sql_where'])) {
            $sql .= ' WHERE ' . $this->aSql['sql_where'];
        } else {
            $this->error('已禁止无条件的删除语句');
        }
        return $sql;
    }

    private function getSth($sql) {
        $request = isset($this->aSql['paramData']) ? $this->aSql['paramData'] : array();
        $input_parameters = $this->link->input_parameters($sql, $request);
        $sth = $this->link->sqlexec($sql, $input_parameters);
        return $sth;
    }

    public function sqlexec() {
        $sth = $this->getSth($this->buildSqlSelect());
        return $sth;
    }

    public function select($fetch_style = \PDO::FETCH_ASSOC) {
        $sth = $this->getSth($this->buildSqlSelect());
        return $sth->fetchAll($fetch_style);
    }

    public function find($fetch_style = \PDO::FETCH_ASSOC) {
        $sth = $this->getSth($this->buildSqlSelect(TRUE));
        return $sth->fetch($fetch_style);
    }

    public function count() {
        $this->field('COUNT(*)');
        $sth = $this->getSth($this->buildSqlSelect(TRUE));
        $row = $sth->fetch(\PDO::FETCH_NUM);
        return empty($row) ? 0 : intval($row[0]);
    }

    public function fetch($fetch_style = \PDO::FETCH_ASSOC) {
        $sth = $this->getSth($this->buildSqlSelect(TRUE));
        return $sth->fetch($fetch_style);
    }

    public function update($data = NULL) {
        $sth = $this->getSth($this->buildSqlUpdate($data));
        return $sth->rowCount();
    }

    public function insert($data) {
        if (is_array($data)) {
            $this->data($data);
        }
        $sql = $this->buildSqlInsert();
        $this->getSth($sql);
        $lastInsertId = $this->link->lastInsertId();
        $this->check_autoid();
        return $lastInsertId;
    }

    public function insertWhenNotExists($data) {
        if (is_array($data)) {
            $this->data($data);
        }
        $sql = $this->buildSqlInsertWhenNotExists();
        $this->getSth($sql);
        return $this->link->lastInsertId();
    }

    public function ignore($bool = TRUE) {
        $this->aSql['ignore'] = $bool;
        return $this;
    }

    public function add($data = NULL) {
        return $this->insert($data);
    }

    public function addwne($data = NULL) {
        return $this->insertWhenNotExists($data);
    }

    public function save($data = NULL) {
        $sth = $this->getSth($this->buildSqlUpdate($data));
        return $sth->rowCount();
    }

    public function addsave($data) {
        $row = $this->lock()->find();
        if ($row) {
            $this->save($data);
        } else {
            $data = array_merge($data, $this->aSql['where']);
            $this->add($data);
        }
    }

    public function delete() {
        $this->getSth($this->buildSqlDelete());
    }

    private function check_autoid_enable($tableName) {
        $setting = $this->link->setting;
        $check_autoid_enable = isset($setting['check_autoid_enable']) ? $setting['check_autoid_enable'] : FALSE;
        if (!isset($setting['Tables'])) {
            return $check_autoid_enable;
        }
        $Tables = $setting['Tables'];
        if (!isset($Tables[$tableName])) {
            return $check_autoid_enable;
        }
        if (!isset($Tables[$tableName]['check_autoid_enable'])) {
            return $check_autoid_enable;
        }
        return $Tables[$tableName]['check_autoid_enable'];
    }

    private function check_autoid() {
        $tableName = $this->aSql['table'];
        if (!$this->check_autoid_enable($this->aSql['table_raw'])) return;
        $fieldName = 'id';
        $sql = "SELECT {$fieldName} FROM {$tableName} ORDER BY {$fieldName} DESC LIMIT 2";
        $sth = $this->getSth($sql);
        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);
        if (count($rows) < 2) {
            return;
        }
        if ($rows[0][$fieldName] - $rows[1][$fieldName] > 1) {
            $this->error("[ID异常] 在[{$tableName}]表");
        }
    }

    private function error($message, $param2 = '提示') {
        $exCode = -2;
        if (is_numeric($param2)) {
            $exCode = $param2;
        }
        $ex = new Exception($message, $exCode);
        if (is_string($param2)) {
            $ex->title = $param2;
        }
        throw $ex;
    }
}
