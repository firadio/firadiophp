<?php

return function($ots_firadio1, $email, $password) {
    $row = $ots_firadio1->getRow('email', $email);
    if (empty($row)) {
        $this->error('账号未注册');
    }
    $user_PK0 = $row['user_PK0'];
    $row = $ots_firadio1->getRow('user', $user_PK0);
    if (empty($row)) {
        $this->error('not find user_PK0');
    }
    if ($row['password'] !== $password) {
        $this->error('密码不正确');
    }
    // 密码验证通过后应该生成SESSION_ID
    $this->message('登录成功！');
};
