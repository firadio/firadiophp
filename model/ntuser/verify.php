<?php

return function($oDb2, $qquin) {
    $this->model_error('1234134');
    $field = 'id,username,verified,cpu_balance';
    $where = 'qquin=:qquin AND ISNULL(deleted)';
    $param = array('qquin' => $qquin);
    $rows = $oDb2->sql()->table('ntuser_user')->field($field)->where($where)->param($param)->lock()->select();
    return $row;
};
