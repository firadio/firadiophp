<?php

/*
 * 字符串处理
 */

namespace FiradioPHP\System;

use FiradioPHP\F;

class Text {

    public $html_charset = 'utf-8';

    public function getarrbyhtml($html, $tarname, $outtype = 1) {
        //$outtype(0:包含整个,1:只有里面的)
        $matches = array();
        preg_match_all('/<' . $tarname . '[^>]*>([\s\S]*?)<\/' . $tarname . '>/i', $html, $matches);
        return ($matches[$outtype]);
    }

    public function getstr1($strall, $str1, $str2) {//从前面开始找
        $i1 = mb_strpos($strall, $str1, 0, $this->html_charset);
        if (!is_int($i1)) {
            return '';
        }//str1没找到！
        $i1R = $i1 + mb_strlen($str1, $this->html_charset);
        if (!$str2) {
            return(mb_substr($strall, $i1R, NULL, $this->html_charset));
        }
        $i2 = mb_strpos($strall, $str2, $i1 + mb_strlen($str1, $this->html_charset), $this->html_charset);
        if (!is_int($i2)) {
            return '';
        }//str2都没找到！
        return(mb_substr($strall, $i1R, $i2 - $i1R, $this->html_charset));
    }

    public function getstr2($strall, $str1, $str2) {//从后面开始找
        if ($str2 != "") {
            $i2 = mb_strrpos($strall, $str2, 0, $this->html_charset);
        }
        if (!is_int($i2)) {
            return '';
        }//str2都没找到！
        if ($str1 != "") {
            $i1 = mb_strrpos($strall, $str1, -(mb_strlen($strall, $this->html_charset) - $i2), $this->html_charset);
        }
        if (!is_int($i1)) {
            return '';
        }//str1没找到！
        return mb_substr($strall, $i1 + mb_strlen($str1, $this->html_charset), $i2 - $i1 - mb_strlen($str1, $this->html_charset));
    }

}
