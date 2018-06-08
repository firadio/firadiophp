<?php

namespace FiradioPHP\Crypt;

/**
 * Description of Aes256
 *
 * @author asheng
 */
class Aes256 {

    //密钥
    private $_app_key;

    public function __construct($config) {
        $this->_app_key = $config['secrect_key'];
    }

    /**
     * 加密方法
     * @param string $str
     * @return string
     */
    public function encrypt($str) {
        //AES, 128 ECB模式加密数据
        $screct_key = $this->_app_key;
        //$screct_key = base64_decode($screct_key);
        $str = trim($str);
        $str = $this->addPKCS7Padding($str);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
        $encrypt_str = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $screct_key, $str, MCRYPT_MODE_ECB, $iv);
        return base64_encode($encrypt_str);
    }

    /**
     * 解密方法
     * @param string $str
     * @return string
     */
    public function decrypt($str) {
        //AES, 128 ECB模式加密数据
        $screct_key = $this->_app_key;
        $str = base64_decode($str);
        //$screct_key = base64_decode($screct_key);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
        $encrypt_str = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $screct_key, $str, MCRYPT_MODE_ECB, $iv);
        $encrypt_str = trim($encrypt_str);
        $encrypt_str = $this->stripPKSC7Padding($encrypt_str);
        return $encrypt_str;
    }

    /**
     * 填充算法
     * @param string $source
     * @return string
     */
    private function addPKCS7Padding($source) {
        $source = trim($source);
        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $pad = $block - (strlen($source) % $block);
        if ($pad <= $block) {
            $char = chr($pad);
            $source .= str_repeat($char, $pad);
        }
        return $source;
    }

    /**
     * 移去填充算法
     * @param string $source
     * @return string
     */
    private function stripPKSC7Padding($source) {
        $source = trim($source);
        $char = substr($source, -1);
        $num = ord($char);
        if ($num > 32)
            return $source;
        $source = substr($source, 0, -$num);
        return $source;
    }

}
