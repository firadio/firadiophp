<?php

namespace FiradioPHP\Math;

class Gps {

    public function __construct() {
        register_shutdown_function(array($this, 'shutdown_function'));
    }

    private function is_cli() {
        return preg_match("/cli/i", php_sapi_name()) ? true : false;
    }

    public function shutdown_function() {
        $e = error_get_last();
        if ($this->is_cli()) {
            print_r($e);
        }
    }

    /**
     * @desc根据两点间的经纬度计算距离
     * @paramfloat $lat纬度值
     * @paramfloat $lng经度值
     */
    function getDistance($lat1, $lng1, $lat2, $lng2) {
        $earthRadius = 6367000; //approximate radius of earth in meters

        /*
          Convert these degrees to radians
          to work with the formula
         */

        $lat1 = ($lat1 * pi()) / 180;
        $lng1 = ($lng1 * pi()) / 180;

        $lat2 = ($lat2 * pi()) / 180;
        $lng2 = ($lng2 * pi()) / 180;

        /*
          Using the
          Haversine formula
          http://en.wikipedia.org/wiki/Haversine_formula
          calculate the distance
         */
        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;
        return round($calculatedDistance);
    }

}
