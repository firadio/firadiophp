<?php

namespace FiradioPHP\Image;

class QrCode {

    public function __construct() {
        
    }

    public function getImage($chl) {
        /*
         * 参考https://github.com/endroid/qr-code
         * composer require endroid/qr-code
         */
        $qrCode = new \Endroid\QrCode\QrCode($chl);
        $this->ContentType = $qrCode->getContentType();
        return $qrCode->writeString();
    }

}
