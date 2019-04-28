<?php

return function($oRes, $mqtt, $a = 'Hello World') {
    $message = date('Y-m-d H:i:s');
    $mqtt->publish('testtopic/1', $message);
    $oRes->message($a);
};
