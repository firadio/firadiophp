<?php

return function($oDb2, $username) {
    $getUsername = function ($qquin) use ($oDb2) {
        $oDb2->rollback();
        $oDb2->beginTransaction();
        $ntuser_qq_row = $oDb2->sql()->table('ntuser_qq')->where(array('qquin' => $qquin))->find();
        if (empty($ntuser_qq_row)) {
            $rows = $oDb2->sql()->table('ntuser_user')->where(array('qquin' => $qquin))->select();
            if (empty($rows)) {
                $oDb2->rollback();
                $this->model_error('QQ:' . $qquin . '尚未在云平台申请过【远程用户】');
            }
            $oDb2->rollback();
            $this->model_error($this->model_qq_mapkey_retmsg($oDb2, $qquin, 'choiceuser', $rows, 'username', '请先选择您要绑定的【远程用户】'));
        }
        $oDb2->rollback();
        return isset($ntuser_qq_row['username']) ? $ntuser_qq_row['username'] : '';
    };
    if (is_numeric($username)) {
        $username = $getUsername($username);
    }
    $oDb2->rollback();
    $oDb2->beginTransaction();
    $field = 'id,username,qquin,deleted';
    $where = array();
    $where['username'] = $username;
    $row = $oDb2->sql()->table('ntuser_user')->field($field)->where($where)->find();
    if (empty($row)) {
        $this->model_error($username . ' is empty in row');
    }
    if (!empty($row['deleted'])) {
        $this->model_error($row['username'] . '该账号已被删除，请使用【绑定】命令重新绑定');
    }
    return $row;
    $field = 'id,username,qquin,cpu_balance,verified,processed,requested,fetched';
    $where = 'qquin=:qquin AND ISNULL(deleted)';
    $param = array('qquin' => $qquin);
    $row = $oDb2->sql()->table('ntuser_user')->field($field)->where($where)->param($param)->order('ISNULL(processed),id')->lock()->find();
    if (empty($row)) {
        return $row;
        // $this->model_error("操作失败，请先到飞儿云平台登记[Windows独立帐号]");
    }
    if (!empty($row['processed'])) {
        if (empty($row['verified'])) {
            $data = array();
            $data['verified'] = 'CURRENT_TIMESTAMP()';
            $data['verify_message'] = '开通后的QQ机器人验证';
            $oDb2->sql()->table('ntuser_user')->where(array('id' => $row['id']))->save($data);
        }
        return $row;
    }
    if (empty($row['requested'])) {
        $row_ntuser = $oDb2->sql()->table('ntuser')->where(array('SamAccountName' => $row['username']))->find();
        if ($row_ntuser) {
            $msg = "开通失败，您提交的{$row['username']}已经存在";
            $msg .= "\r\n如果您是早期开通的用户，请联系QQ:" . CONFIG_ADMIN_QQ;
            $this->model_error($msg);
        }
        $data = array();
        $data['requested'] = 'CURRENT_TIMESTAMP()';
        $data['verified'] = 'CURRENT_TIMESTAMP()';
        $data['verify_message'] = 'QQ机器人自助开通';
        $data['request_action'] = 'open';
        $data['fetched'] = NULL;
        $oDb2->sql()->table('ntuser_user')->where(array('id' => $row['id']))->save($data);
        $oDb2->commit();
        $msg = '系统正在为您开通[Windows独立帐号]';
        $msg .= "\r\n您的[远程桌面]连接地址是freevps.firadio.net";
        $msg .= "\r\n用户名{$row['username']}@firadio.net密码请到云平台查看";
        $msg .= "\r\n请等待1分钟后再次发送[签到]指令确认是否开通。";
        $this->model_error($msg);
    }
    if (empty($row['fetched'])) {
        $msg = '系统正在为您开通[Windows独立帐号]';
        $msg .= "\r\n请等待1分钟后再次发送[签到]指令确认是否开通。";
        $msg .= "\r\n如果超过5分钟无反应，请联系QQ:" . CONFIG_ADMIN_QQ;
        $this->model_error($msg);
    }
    $msg = '开通失败，这是由于自动开通程序出现了故障，请联系QQ:' . CONFIG_ADMIN_QQ;
    $this->model_error($msg);
};
