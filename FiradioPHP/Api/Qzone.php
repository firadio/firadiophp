<?php

namespace FiradioPHP\Api;

use FiradioPHP\F;
use FiradioPHP\Socket\Curl;

class Qzone {

    private $aConfig;
    private $aApi = array();
    private $oCurl;
    private $oDb;

    public function __construct($conf) {
        $this->aConfig = $conf;
        $this->oCurl = new Curl();
        $this->oCurl->setHeader('Cookie', 'p_skey=' . $conf['p_skey'] . ';');
    }

    public function setDb($oDb) {
        $this->oDb = $oDb;
    }

    private function getData($path, $aRequest) {
        $sJson = $this->oCurl->get($path, $aRequest);
        $aJson = json_decode($sJson, true);
        return $aJson;
    }

    private function error($message, $title = '提示') {
        $ex = new \Exception($message, -2);
        $ex->title = $title;
        throw $ex;
    }

    public function blog() {
        //curl 'https://h5.qzone.qq.com/webapp/json/mqzone_detail/blog?qzonetoken=85d8f3abe1a124fd26182e1390b877f1bc25bf11bde8b08a4eac5d76847cc5ca6b5f8d56d40b6011a3fdde17091a99f7&g_tk=2012981038&appid=2&uin=309385018&count=20&refresh_type=31&cellid=1498912766&format=json' -H 'Cookie: p_skey=8dGYGbnPP-cDF-ANDgmqopqnN*xohcUYlLq8ea5lNic_;'
        $this->oCurl->setUrlPre('https://h5.qzone.qq.com');
        $aRequest = array();
        $aRequest['qzonetoken'] = $this->aConfig['qzonetoken'];
        $aRequest['g_tk'] = '2012981038';
        $aRequest['appid'] = 2;
        $aRequest['uin'] = $this->aConfig['qquin'];
        $aRequest['count'] = 20;
        $aRequest['refresh_type'] = 31;
        $aRequest['cellid'] = 1498912766;
        $aRequest['format'] = 'json';
        $aJsonData = $this->getData('/webapp/json/mqzone_detail/blog', $aRequest);
        if ($this->oCurl->getHttpStatus() == 200) {
            $comments = $aJsonData['data']['cell_comment']['comments'];
            foreach ($comments as $comment) {
                $this->readComment($comment);
            }
        }
    }

    private function readComment($comment) {
        if ($comment['replys']) {
            return;
        }
        $msg = $this->oMessage->qq($comment['user']['uin'], $comment['content']);
        print_r($comment['commentid']);
    }

}
