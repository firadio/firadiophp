<?php

return function($ots_firadio1, $email) {
    $row = $ots_firadio1->get('email', $email);
    if (empty($row)) {
        $this->error('账号未注册');
    }
};
