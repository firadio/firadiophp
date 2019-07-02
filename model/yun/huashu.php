<?php

return function($oDb6, $im_account, $content, $voiceMediaId) {
    if ($content === '【收到不支持的消息类型，暂无法显示】') {
        return;
    }
    $msgkey = trim($content);
    $msgkey = str_replace("\r", '', $msgkey);
    $msgkey = str_replace("\n", '', $msgkey);
    if (empty($msgkey)) {
        return '';
    }
    if (!isset($GLOBALS['userstat'])) {
        $GLOBALS['userstat'] = array();
    }
    if (!isset($GLOBALS['userstat'][$im_account])) {
        $GLOBALS['userstat'][$im_account] = array();
    }
    if (!isset($GLOBALS['userstat'][$im_account]['mode'])) {
        $GLOBALS['userstat'][$im_account]['mode'] = '';
    }
    if (!isset($GLOBALS['userstat'][$im_account]['cmdkey'])) {
        $GLOBALS['userstat'][$im_account]['cmdkey'] = array();
    }
    if (isset($GLOBALS['userstat'][$im_account]['cmdkey'][$msgkey])) {
        $cmdkey = $GLOBALS['userstat'][$im_account]['cmdkey'][$msgkey];
        if ($cmdkey === '获取语音消息') {
            $sql = "SELECT voiceMediaId FROM {tablepre}response WHERE id=:response_id";
            $row = $oDb6->fetchOne($sql, array('response_id' => $GLOBALS['userstat'][$im_account]['response_id']));
            return 'voice:' . $row['voiceMediaId'];
        }
        /*
          if ($cmdkey === '进入答题模式') {
          $GLOBALS['userstat'][$im_account]['mode'] = '答题模式';
          $sql = "SELECT id,message FROM {tablepre}request req WHERE req.id=:request_id";
          $row = $oDb6->fetchOne($sql, array('request_id' => $GLOBALS['userstat'][$im_account]['request_id']));
          $msg = '您已进入答题模式' . "\r\n";
          $msg .= "对方说：". $row['message'] . "\r\n";
          $msg .= '请输入您的答复(支持语音消息)';
          return $msg;
          }// */
        if ($cmdkey === '查看话题') {
            
        }
    }
    if ($GLOBALS['userstat'][$im_account]['mode'] == '答题模式') {
        $GLOBALS['userstat'][$im_account]['mode'] = '查询模式';
        $msg = "感谢您提供的答复，\r\n如被采纳后您将获得10个积分";
        $obj = $oDb6->sql()->table('response');
        $row = array();
        $row['im_account'] = $im_account;
        $row['message'] = $msgkey;
        $row['voiceMediaId'] = $voiceMediaId;
        $row['request_id'] = $GLOBALS['userstat'][$im_account]['request_id'];
        $insert_id = $obj->add($row);
        $oDb6->commit();
        $msg .= "\r\n回复[DF{$insert_id}]可查看您的答复";
        return $msg;
    }
    $pattern = "/^DF{1}(\d{1,4})$/i";
    if (preg_match($pattern, $msgkey, $matches)) {
        $response_id = $matches[1];
        $sql = "SELECT res.id res_id,res.message res_message,res.voiceMediaId,req.id req_id,req.message req_message FROM {tablepre}response res LEFT JOIN {tablepre}request req ON req.id=res.request_id WHERE res.id=:response_id";
        $row = $oDb6->fetchOne($sql, array('response_id' => $response_id));
        if (empty($row)) {
            return '您输入的答复代码不存在';
        }
        $GLOBALS['userstat'][$im_account]['response_id'] = $response_id;
        $GLOBALS['userstat'][$im_account]['cmdkey']['1'] = '获取语音消息';
        $GLOBALS['userstat'][$im_account]['cmdkey']['2'] = '查看话题';
        $msg = "答复代码：DF{$response_id}\r\n";
        $msg .= "对方说：" . $row['req_message'] . "\r\n";
        $msg .= "你回答：" . $row['res_message'] . "\r\n";
        if ($row['voiceMediaId']) {
            $msg .= "回复[1]获取语音消息\r\n";
        }
        $msg .= "回复HT{$row['req_id']}查看该话题\r\n";
        return $msg;
    }
    $pattern = "/^HD{1}(\d{1,4})$/i";
    if (preg_match($pattern, $msgkey, $matches)) {
        $msg = '您已进入答题模式' . "\r\n";
        $request_id = $matches[1];
        $GLOBALS['userstat'][$im_account]['request_id'] = $request_id;
        $GLOBALS['userstat'][$im_account]['mode'] = '答题模式';
        $sql = "SELECT id,message FROM {tablepre}request req WHERE req.id=:request_id";
        $row = $oDb6->fetchOne($sql, array('request_id' => $request_id));
        $msg .= "对方说：" . $row['message'] . "\r\n";
        $msg .= '请输入您的答复(支持语音消息)';
        return $msg;
    }
    $pattern = "/^HT{1}(\d{1,4})$/i";
    if (preg_match($pattern, $msgkey, $matches)) {
        $request_id = $matches[1];
        $GLOBALS['userstat'][$im_account]['request_id'] = $request_id;
        $sql = "SELECT id,message,(SELECT COUNT(*) FROM {tablepre}response res WHERE req.id=res.request_id)res_count FROM {tablepre}request req WHERE req.id=:request_id";
        $row_ht = $oDb6->fetchOne($sql, array('request_id' => $request_id));
        //print_r($row_ht);
        $msg = "话题代码：HT{$row_ht['id']}\r\n";
        $msg .= "话题内容：{$row_ht['message']}\r\n";
        if ($row_ht['res_count'] > 0) {
            $msg .= "找到" . $row_ht['res_count'] . "个答复\r\n";
            $sql = 'SELECT * FROM {tablepre}response res WHERE res.request_id=:request_id';
            $rows = $oDb6->fetchAll($sql, array('request_id' => $request_id));
            foreach ($rows as $row) {
                $msg .= '====================' . "\r\n";
                $msg .= "[DF{$row['id']}] {$row['message']}\r\n";
            }
            $msg .= '====================' . "\r\n";
            $msg .= '回复[方框内]的答复代码查看详情' . "\r\n";
        } else {
            $msg .= "该话题暂时无人解答\r\n";
        }
        $GLOBALS['userstat'][$im_account]['cmdkey']['1'] = '进入答题模式';
        $msg .= "回复[HD{$row_ht['id']}]进入答题模式";
        return $msg;
    }
    $sql = 'SELECT id,message,(SELECT COUNT(*) FROM {tablepre}response res WHERE req.id=res.request_id)res_count FROM {tablepre}request req WHERE ISNULL(req.deleted) AND req.message LIKE :message';
    $rows = $oDb6->fetchAll($sql, array('message' => '%' . $msgkey . '%'));
    if (empty($rows)) {
        $msg = '';
        $obj = $oDb6->sql()->table('request');
        if ($row = $obj->where(array('message' => $msgkey))->find()) {
            $msg .= "查询话题：{$msgkey}";
            $msg .= "\r\n您要查的话题已被删除";
        } else {
            $row = array();
            $row['im_account'] = $im_account;
            $row['message'] = $msgkey;
            $insert_id = $obj->add($row);
            $oDb6->commit();
            $msg .= "查询话题：{$msgkey}";
            $msg .= "\r\n您要查的话题暂时无人解答";
            $msg .= "\r\n回复HD{$insert_id}可提供您的答案";
        }
        return $msg;
    }
    $msg = "找到" . count($rows) . "条相似话题\r\n";
    foreach ($rows as $row) {
        $msg .= "[HT{$row['id']}] {$row['message']} ({$row['res_count']}个回答)\r\n";
    }
    $msg .= '回复[方框内]的话题编码查看详情';
    return $msg;
};
