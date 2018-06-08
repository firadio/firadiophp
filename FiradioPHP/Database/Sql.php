<?php

/**
 * Description of sql
 *
 * @author asheng
 */

namespace FiradioPHP\Database;

class Sql {

    private $aSql = array();
    public $link;

    public function __construct($link) {
        $this->link = $link;
    }

    public function field($field) {
        $this->aSql['field'] = $field;
        return $this;
    }

    public function table($table) {
        if (strpos($table, '{tablepre}') === FALSE) {
            //如果没有带上前缀标记就自动加上
            $table = $this->link->tablepre . $table;
        } else {
            //有了的就替换好
            $table = str_replace('{tablepre}', $this->link->tablepre, $table);
        }
        $this->aSql['table'] = $table;
        return $this;
    }

    public function tableField($tableField) {
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

    public function where($where) {
        if (is_array($where)) {
            if (!isset($this->aSql['paramData'])) {
                $this->aSql['paramData'] = array();
            }
            $where_keys = array();
            foreach ($where as $key => $val) {
                $table = '';
                $field = $key;
                $posi = strpos($key, '.');
                if ($posi !== FALSE) {
                    $table = substr($key, 0, $posi);
                    if (!preg_match('/^[a-z][0-9a-z_]{0,10}$/i', $table)) {
                        continue;
                    }
                    // 在包含小数点的时候，取小数点后的为字段
                    $field = substr($key, $posi + 1);
                    $key = $table . '_' . $field;
                }
                if (!preg_match('/^[a-z][0-9a-z_]{1,19}$/i', $field)) {
                    //必须字母开头，可以包含字母和数字还有下划线
                    continue;
                }
                $str = '`' . $field . '`';
                if ($table) {
                    $str = '`' . $table . '`.' . $str;
                }
                if ($val === NULL) {
                    $str = 'ISNULL(' . $str . ')';
                    $where_keys[] = $str;
                    continue;
                }
                $str .= '=:' . $key;
                $where_keys[] = $str;
                $this->aSql['paramData'][$key] = $val;
            }
            $this->aSql['where'] = implode(' AND ', $where_keys);
            return $this;
        }
        $this->aSql['where'] = $where;
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
        foreach ($data as $key => $val) {
            if (!preg_match('/^[a-z][0-9a-z_]{1,19}$/i', $key)) {
                //必须字母开头，可以包含字母和数字还有下划线
                continue;
            }
            if ($val === NULL) {
                $this->aSql['paramField'][$key] = 'NULL';
                continue;
            }
            if ($val === 'CURRENT_TIMESTAMP()') {
                $this->aSql['paramField'][$key] = 'CURRENT_TIMESTAMP()';
                continue;
            }
            $this->aSql['paramField'][$key] = ':' . $key;
            $this->aSql['paramData'][$key] = $val;
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
        if (isset($this->aSql['where']) && !empty($this->aSql['where'])) {
            $sql .= ' WHERE ' . $this->aSql['where'];
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
        if (isset($this->aSql['where']) && !empty($this->aSql['where'])) {
            $sql .= ' WHERE ' . $this->aSql['where'];
        }
        return $sql;
    }

    public function buildSqlInsert($data = NULL) {
        if (!empty($data)) {
            $this->data($data);
        }
        $fields = $values = array();
        foreach ($this->aSql['paramField'] as $field => $val) {
            $fields[] = '`' . $field . '`';
            $values[] = $val;
        }
        $sql = 'INSERT ' . $this->aSql['table'] . '(' . implode(',', $fields) . ')VALUES(' . implode(',', $values) . ')';
        return $sql;
    }

    private function getSth($sql) {
        $request = isset($this->aSql['paramData']) ? $this->aSql['paramData'] : array();
        $input_parameters = $this->link->input_parameters($sql, $request);
        $sth = $this->link->sqlexec($sql, $input_parameters);
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

    public function fetch($fetch_style = \PDO::FETCH_ASSOC) {
        $sth = $this->getSth($this->buildSqlSelect(TRUE));
        return $sth->fetch($fetch_style);
    }

    public function update($data = NULL) {
        $sth = $this->getSth($this->buildSqlUpdate($data));
        return $sth->rowCount();
    }

    public function insert($data = NULL) {
        $this->getSth($this->buildSqlInsert($data));
        return $this->link->lastInsertId();
    }

    public function add($data = NULL) {
        $this->getSth($this->buildSqlInsert($data));
        return $this->link->lastInsertId();
    }

    public function save($data = NULL) {
        $sth = $this->getSth($this->buildSqlUpdate($data));
        return $sth->rowCount();
    }

}
