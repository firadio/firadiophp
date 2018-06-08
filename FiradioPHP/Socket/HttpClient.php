<?php

namespace FiradioPHP\Socket;

use FiradioPHP\F;

class HttpClient {

    private $config = array();
    private $header = array();
    public $cli;

    public function __construct($url = NULL) {
        if (is_string($url)) {
            $this->config['url'] = $url;
        }
    }

    public function setHostPort($sHost, $iPort) {
        if (class_exists('\Swoole\Http\Client')) {
            $this->cli = new \Swoole\Http\Client($sHost, $iPort);
        }
    }

    public function setHeader($key, $value) {
        $this->header[$key] = $value;
    }

    public function post() {
        /*
          $aHeader['Content-Length'] = strlen($sData);
          $sUrl = $this->config['url'] . $sPath;
          $request = F::$aInstances['httpclient']->request('POST', $url, $header);
          $request->on('response', function (Response $response) {
          var_dump($response->getHeaders());
          $response->on('data', function ($chunk) {
          echo $chunk;
          });
          $response->on('end', function () {
          echo 'DONE' . PHP_EOL;
          });
          });
          $request->end($sData);
         */
    }

    public function post_json($sPath, $aData, $fCallback = NULL) {
        $aHeader = $this->header;
        $aHeader['Content-Type'] = 'application/json';
        $sJson = json_encode($aData);
        if (!class_exists('\Swoole\Http\Client')) {
            return;
        }
        $this->cli->setHeaders($aHeader);
        if (empty($fCallback)) {
            $fCallback = function($cli) {
                //var_dump($cli->headers);
                //var_dump($cli->body);
            };
        }
        $this->cli->post($sPath, $sJson, $fCallback);
    }

}
