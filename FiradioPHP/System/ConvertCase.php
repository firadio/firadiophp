<?php

/*
 * 下划线转驼峰
 */

namespace FiradioPHP\System;

use FiradioPHP\F;

class ConvertCase {

    static private function eachArray($arr, $fun) {
        $result = array();
        foreach ($arr as $key => $item) {
            if (is_array($item) || is_object($item)) {
                $result[self::$fun($key)] = self::eachArray($item, $fun);
            } else {
                $result[self::$fun($key)] = $item;
            }
        }
        return $result;
    }

    /*
     * 下划线转驼峰(Camel|Hump)
     */

    static public function toCamel($str) {
        if (is_array($str) || is_object($str)) {
            return self::eachArray($str, __FUNCTION__);
        }
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return $str;
    }

    /*
     * 驼峰(Camel|Hump)转下划线
     */

    static public function toUnderline($str) {
        if (is_array($str) || is_object($str)) {
            return self::eachArray($str, __FUNCTION__);
        }
        $str = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '_' . strtolower($matches[0]);
        }, $str);
        return $str;
    }

}
