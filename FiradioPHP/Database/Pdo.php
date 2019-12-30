<?php

namespace FiradioPHP\Database;

use \PDOException;
use \Exception;
use FiradioPHP\F;

class Pdo {
    /*
     * http://php.net/manual/zh/class.pdo.php
     */

    private $setting = array();
    private $errorCount = 0;
    private $errorCountMax = 2; //最大失败次数
    private $sql;
    public $tablepre = '';
    private $pdo_parent;

    public function __get($name) {
        if ($name === 'setting') {
            return $this->setting;
        }
        throw new Exception("cannot get property name=$name");
    }

    private function dsn($driver, $conf = array()) {
        $dsn = $driver . ':';
        foreach ($conf as $k => $v) {
            if (in_array($k, array('host', 'port', 'dbname'))) {
                $dsn .= "${k}=${v};";
            }
        }
        return $dsn;
    }

    private function error($message, $title = '提示') {
        $ex = new Exception($message, -2);
        $ex->title = $title;
        throw $ex;
    }

    public function __construct(array $setting) {
        $this->tablepre = $setting['tablepre'];
        $this->connect($setting);
    }

    public function connect($setting = NULL) {
        if (!empty($setting)) {
            //有新配置就更新
            $this->setting = $setting;
        } else if (!empty($this->setting)) {
            //载入上次的配置
            $setting = $this->setting;
        } else {
            throw new Exception('setting is empty');
        }
        $dsn = $this->dsn($setting['driver'], $setting);
        $options = array();
        if (isset($setting['options'])) {
            foreach ($setting['options'] as $key => $option) {
                $options[constant("PDO::{$key}")] = $option;
            }
        }
        $this->pdo_parent = new \PDO($dsn, $setting['username'], $setting['password'], $options);
        foreach ($setting["attributes"] as $k => $v) {
            $val = is_string($v) ? constant("PDO::{$v}") : $v;
            $this->pdo_parent->setAttribute(constant("PDO::{$k}"), $val);
        }
    }

    public function inTransaction() {
        return $this->pdo_parent->inTransaction();
    }

    public function begin() {
        return $this->beginTransaction();
    }

    public function beginTrans() {
        return $this->beginTransaction();
    }

    public function beginTransaction() {
        $ret = null;
        try {
            //Warning: Error while sending QUERY packet. PID=7888 in
            if ($this->inTransaction()) {
                return $ret;
            }
            $ret = @$this->pdo_parent->beginTransaction();
            $this->errorCount = 0; //重置错误计数
            return $ret;
        } catch (PDOException $ex) {
            //SQLSTATE[HY000]: General error: 2006 MySQL server has gone away
            if ($this->errorCount >= $this->errorCountMax) {
                $this->errorCount = 0; //重置错误计数
                echo 'errorCountMax';
                throw $ex;
            }
            $this->errorCount++;
            $sCode = $ex->getCode();
            $iErrno = intval($ex->errorInfo[1]);
            if ($sCode === 'HY000' || in_array($iErrno, array(2006, 2013))) {
                //服务端断开时重连一次
                $this->connect();
                return $this->beginTransaction();
            }
            throw $ex;
        } catch (Exception $ex) {
            if ($this->errorCount >= $this->errorCountMax) {
                $this->errorCount = 0; //重置错误计数
                echo 'errorCountMax';
                throw $ex;
            }
            $this->errorCount++;
            if (1
                && $ex->getCode() === 0
                && $ex->getMessage() === 'PDO::beginTransaction(): MySQL server has gone away'
            ) {
                //服务端断开时重连一次
                $this->connect();
                return $this->beginTransaction();
            }
            throw $ex;
        }
        return $ret;
    }

    public function rollback() {
        if ($this->inTransaction()) {
            $this->pdo_parent->rollback();
        }
    }

    public function commit() {
        return $this->pdo_parent->commit();
    }

    public function prepare($sql, $driver_options = array()) {
        //Fatal error: Access level to FiradioPHP\Database\Pdo::prepare() must be public (as in class PDO)
        $statement = str_replace('{tablepre}', $this->tablepre, $sql);
        return $this->pdo_parent->prepare($statement, $driver_options);
    }

    public function sqlexec($sql, $parameters) {
        //执行SQL语句
        $sth = $this->prepare($sql);
        try {
            //Warning: Error while sending QUERY packet. PID=7888 in
            $sth->execute($parameters);
            $this->errorCount = 0; //重置错误计数
        } catch (PDOException $ex) {
            if ($this->errorCount >= $this->errorCountMax) {
                $this->errorCount = 0; //重置错误计数
                throw $ex;
            }
            $this->errorCount++;
            if (!in_array($ex->errorInfo[1], array(2006, 2013))) {
                $error = array();
                $error['message'] = $ex->getMessage();
                $error['queryString'] = $sth->queryString;
                $error['parameters'] = $parameters;
                F::error($error, $ex);
                throw $ex;
            }
            //服务端断开时重连一次
            $this->connect();
            $sth = $this->sqlexec($sql, $parameters);
        }
        return $sth;
    }

    public function callProc($procName, $params = array()) {
        $sql = "CALL {$procName}";
        if (count($params) > 0) {
            $place_holders = implode(',', array_fill(0, count($params), '?'));
            $sql .= "({$place_holders})";
        }
        return $this->sqlexec($sql, $params);
    }

    public function callProc_fetchOne($procName, $params) {
        $sth = $this->callProc($procName, $params);
        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    public function call($procName, $params = array()) {
        $sth = $this->callProc($procName, $params);
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function func($funcName, $params = array()) {
        $sql = "SELECT {$funcName}";
        if (count($params) > 0) {
            $place_holders = implode(',', array_fill(0, count($params), '?'));
            $sql .= "({$place_holders})";
        }
        $sth = $this->sqlexec($sql, $params);
        return $sth->fetch(\PDO::FETCH_NUM)[0];
    }

    public function input_parameters($sql, $request = array()) {
        //根据SQL语句里面的参数自动生成$input_parameters
        $reg = '/\:([a-z][a-z0-9_]+)/i';
        //$reg = '/\:([\x{4e00}-\x{9fa5}A-Za-z0-9_]+)/u';
        $matches = array();
        preg_match_all($reg, $sql, $matches);
        $input_parameters = array();
        foreach ($matches[1] as $name) {
            $value = isset($request[$name]) ? $request[$name] : '';
            $input_parameters[':' . $name] = $value;
        }
        return $input_parameters;
    }

    public function query($sql, $request = array()) {
        $input_parameters = $this->input_parameters($sql, $request);
        $sth = $this->sqlexec($sql, $input_parameters);
        return $sth;
    }

    public function fetchOne($sql, $request = array()) {
        $input_parameters = $this->input_parameters($sql, $request);
        $sth = $this->sqlexec($sql, $input_parameters);
        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    public function fetchAll($sql, $request = array()) {
        $input_parameters = $this->input_parameters($sql, $request);
        $sth = $this->sqlexec($sql, $input_parameters);
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function update($table, $data, $where = NULL, $where_param = array()) {
        $sSets = array();
        $input_parameters = array();
        foreach ($data as $key => $val) {
            if (!preg_match('/^[a-z][0-9a-z_]{1,19}$/i', $key)) {
                //必须字母开头，可以包含字母和数字还有下划线
                continue;
            }
            if ($val === NULL) {
                $sSets[] = '`' . $key . '`=NULL';
                continue;
            }
            if ($val === 'CURRENT_TIMESTAMP()') {
                $sSets[] = '`' . $key . '`=CURRENT_TIMESTAMP()';
                continue;
            }
            $sSets[] = '`' . $key . '`=:' . $key;
            $input_parameters[':' . $key] = $val;
        }
        //下面处理SQL语句中的WHERE条件
        $where_str = '1';
        if (is_array($where)) {
            $where_keys = array();
            foreach ($where as $key => $val) {
                if (!preg_match('/^[a-z][0-9a-z_]{1,19}$/i', $key)) {
                    //必须字母开头，可以包含字母和数字还有下划线
                    continue;
                }
                $where_keys[] = '`' . $key . '`=:' . $key;
                $input_parameters[':' . $key] = $val;
            }
            $where_str = implode(' AND ', $where_keys);
        } else if (is_string($where)) {
            $where_str = $where;
            if (!is_array($where_param)) {
                throw new Exception("必须是一个数组，才能处理");
                //return false;
            }
            $param = $this->input_parameters($where, $where_param);
            foreach ($param as $key => $val) {
                $input_parameters[$key] = $val;
            }
        }
        $sql = 'UPDATE `' . $this->tablepre . $table . '` SET ' . implode(',', $sSets) . ' WHERE ' . $where_str;
        $sth = $this->sqlexec($sql, $input_parameters);
        return $sth->rowCount();
    }

    public function insert($table, $data) {
        $input_parameters = array();
        $fields = array();
        $values = array();
        foreach ($data as $key => $val) {
            if (!preg_match('/^[a-z][0-9a-z_]{1,19}$/i', $key)) {
                //必须字母开头，可以包含字母和数字还有下划线
                continue;
            }
            $fields[] = '`' . $key . '`';
            if ($val === 'CURRENT_TIMESTAMP()') {
                $values[] = 'CURRENT_TIMESTAMP()';
                continue;
            }
            $values[] = ':' . $key;
            $input_parameters[':' . $key] = $val;
        }
        $sql = 'INSERT `' . $this->tablepre . $table . '`(' . implode(',', $fields) . ')VALUES(' . implode(',', $values) . ')';
        $this->sqlexec($sql, $input_parameters);
        return $this->pdo_parent->lastInsertId();
    }

    public function lastInsertId() {
        return $this->pdo_parent->lastInsertId();
    }

    public function sql() {
        return new Sql($this);
        if (empty($this->sql)) {
            $this->sql = new Sql($this);
        }
        return $this->sql;
    }

    public function page($oSql) {
        return new Page($oSql);
    }

    public function database_exist($dbname) {
        $sql = 'SELECT information_schema.SCHEMATA.SCHEMA_NAME FROM information_schema.SCHEMATA where SCHEMA_NAME=:dbname';
        $request = array();
        $request['dbname'] = $dbname;
        $row = $this->fetchOne($sql, $request);
        return !empty($row);
    }

    public function database_create($dbname, $skip_exist = true) {
        if (!preg_match("/^[0-9a-z_]{1,50}$/i", $dbname)) {
            $this->error('数据库名只能由数字、英文、下划线组成');
        }
        if ($this->database_exist($dbname)) {
            if ($skip_exist) {
                return true;
            }
            $this->error("数据库名[{$dbname}]已经存在");
        }
        $sql = "CREATE DATABASE `{$dbname}`";
        $parameters = array();
        $this->query($sql, $parameters);
        return true;
    }

    public function user_exist($dbuser, $dbhost) {
        $sql = 'SELECT `user`, `host` FROM `mysql`.`user` WHERE `user` = :dbuser AND `host` = :dbhost';
        $request = array();
        $request['dbuser'] = $dbuser;
        $request['dbhost'] = $dbhost;
        $row = $this->fetchOne($sql, $request);
        return !empty($row);
    }

    public function user_create($dbuser, $dbpass, $dbhost = '%') {
        if ($this->user_exist($dbuser, $dbhost)) {
            $this->user_alter($dbuser, $dbpass, $dbhost);
            return true;
        }
        $sql = 'CREATE USER :dbuser@:dbhost IDENTIFIED BY :dbpass';
        $data = array();
        $data['dbhost'] = $dbhost;
        $data['dbuser'] = $dbuser;
        $data['dbpass'] = $dbpass;
        $this->query($sql, $data);
        return true;
    }

    public function user_setpassword($dbuser, $dbpass, $dbhost = '%') {
        $sql = 'SET PASSWORD FOR :dbuser@:dbhost = PASSWORD(:dbpass)';
        $data = array();
        $data['dbhost'] = $dbhost;
        $data['dbuser'] = $dbuser;
        $data['dbpass'] = $dbpass;
        $this->query($sql, $data);
        return true;
    }

    public function user_alter($dbuser, $dbpass, $dbhost = '%') {
        //ALTER USER [IF EXISTS] USER() IDENTIFIED BY 'auth_string'
        $sql = 'ALTER USER IF EXISTS :dbuser@:dbhost IDENTIFIED BY :dbpass';
        $data = array();
        $data['dbhost'] = $dbhost;
        $data['dbuser'] = $dbuser;
        $data['dbpass'] = $dbpass;
        $this->query($sql, $data);
        return true;
    }

    public function grant_privileges($row) {
        $sql = "GRANT ALL ON `{$row['dbname']}`.* TO :dbuser@'%' IDENTIFIED BY :dbpass";
        $this->query($sql, $row);
        return true;
    }

}
