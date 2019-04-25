<?php

namespace FiradioPHP\Api\Aliyun;

use Aliyun\OTS\Consts\ColumnTypeConst;
use Aliyun\OTS\Consts\PrimaryKeyTypeConst;
use Aliyun\OTS\Consts\RowExistenceExpectationConst;
use Aliyun\OTS\OTSClient as OTSClient;
use Aliyun\OTS\OTSServerException as OTSServerException;

/**
 * https://github.com/aliyun/aliyun-tablestore-php-sdk/
 */
class OTS {

    // private $aConfig;
    public $oOTSClient;

    public function __construct($conf = array()) {
        if (extension_loaded('protobuf')) {
            $msg = '检测到会与本系统冲突的 protobuf 扩展，请先移除该扩展';
            throw new \Exception($msg);
        }
        // $this->aConfig = $conf['config'];
        $config = $conf['config'];
        $config['DebugLogHandler'] = function ($message) {
            return;
            $dateStr = date('Y-m-d H:i:s', time());
            print "OTS DEBUG $dateStr $message\n";
        };
        $config['ErrorLogHandler'] = function () {
            return;
            $dateStr = date('Y-m-d H:i:s', time());
            print "OTS ERROR $dateStr $message\n";
        };
        $this->oOTSClient = new OTSClient ($config);
    }

    public function primary_key_string($len = 8) {
        $data = microtime() . ',' . mt_rand(); // 1:取随机值
        $data = md5($data, TRUE); // 2:取二进制MD5
        $data = base64_encode($data); // 3: 取BASE64
        $data = str_replace('/', '', $data); // 4-1: 去掉特殊符号
        $data = str_replace('+', '', $data); // 4-2: 去掉特殊符号
        $data = substr($data, 0, $len); // 只要8个字节
        return array ( // 主键
            array('PK0', $data, PrimaryKeyTypeConst::CONST_STRING)
        );
    }

    public function primary_key_integer($len = 12) {
        $data = microtime() . ',' . mt_rand(); // 1:取随机值
        $data = unpack('Q*', md5($data, TRUE))[1]; // 2:取MD5的无符号长长整型(64位，主机字节序)
        $data = substr($data, 0 - $len); // 取最后12位数字
        if (substr($data, 0, 1) === '0') {
            // 如果第一位是0就换成1
            $data = '1' . substr($data, 1 - strlen($data));
        }
        return array ( // 主键
            array('PK0', $data, PrimaryKeyTypeConst::CONST_INTEGER)
        );
    }

    private function row_to_columns($row) {
        $attribute_columns = array();
        foreach ($row as $name => $value) {
            $attribute_columns[] = array($name, $value);
        }
        return $attribute_columns;
    }

    private function check_response($response) {
        return $response;
    }

    public function putRow_raw($table_name, $key, $row, $condition) {
        $request = array (
            'table_name' => $table_name,
            'condition' => $condition, // condition可以为IGNORE, EXPECT_EXIST, EXPECT_NOT_EXIST
            'primary_key' => $this->primary_key_integer(),
            'attribute_columns' => $this->row_to_columns($row)
        );
        $response = $this->oOTSClient->putRow($request);
        return $response;
    }

    public function addRow($table_name, $key, $row) {
        // 一般用于添加账号，如果账号已存在肯定要报错
        return $this->putRow_raw($table_name, $key, $row, RowExistenceExpectationConst::CONST_EXPECT_NOT_EXIST);
    }

    public function setRow($table_name, $key, $row) {
        // 一般用于修改账号，修改失败肯定要报错
        return $this->putRow_raw($table_name, $key, $row, RowExistenceExpectationConst::CONST_EXPECT_EXIST);
    }

    public function putRow($table_name, $row) {
        // 像日志一样写入数据，无需判断错与否
        return $this->putRow_raw($table_name, NULL, $row, RowExistenceExpectationConst::CONST_IGNORE);
    }

    public function getRow($table_name, $PK0) {
        $request = array (
            'table_name' => $table_name,
            'primary_key' => array ( // 主键
                array('PK0', $PK0)
            ),
            'max_versions' => 1
        );
        try {
            $response = $this->oOTSClient->getRow($request);
            if (empty($response['attribute_columns'])) {
                return FALSE; // 未找到
            }
            $row = array();
            foreach ($response['attribute_columns'] as $col) {
                $row[$col[0]] = $col[1];
            }
            return $row;
        } catch (OTSServerException $e) {
            // 按照你的需要处理这个异常
            $code = intval($e->getOTSErrorCode());
            if ($code === 19) {
                // Request table not exist 
                return FALSE;
            }
        }
        return FALSE;
    }

}
