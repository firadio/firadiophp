<?php

namespace FiradioPHP\Crypt;

class Openssl {

    //密钥
    private $_secrect_key;

    public function __construct($config) {
        $this->_secrect_key = $config['secrect_key'];
    }

    public function aes256_encrypt($string) {
        $encryption_key = $this->_secrect_key;
        $iv = openssl_random_pseudo_bytes(16);
        $data = openssl_encrypt($string, 'AES-256-CBC', $encryption_key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($data);
    }

}
