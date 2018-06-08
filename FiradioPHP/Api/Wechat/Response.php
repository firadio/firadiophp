<?php

namespace FiradioPHP\Api\Wechat;

use \DOMDocument;

class Response {

    private $dom_root;
    private $dom_xml;

    public function __construct() {
        $this->dom_root = new DOMDocument();
        $this->dom_xml = $this->dom_root->appendChild($this->dom_root->createElement('xml'));
        $CreateTime = $this->dom_xml->appendChild($this->dom_root->createElement('CreateTime'));
        $CreateTime->appendChild($this->dom_root->createTextNode(time()));
    }

    public function appendToXml($name, $value, $type = 'CDATA') {
        $child = $this->dom_xml->appendChild($this->dom_root->createElement($name));
        if ($type === 'CDATA') {
            $child->appendChild($this->dom_root->createCDATASection($value));
        } else {
            $child->appendChild($this->dom_root->createTextNode($value));
        }
    }

    public function appendToXml2($plabel, $name, $value, $type = 'CDATA') {
        $child = $this->dom_xml->appendChild($this->dom_root->createElement($plabel));
        $child = $child->appendChild($this->dom_root->createElement($name));
        if ($type === 'CDATA') {
            $child->appendChild($this->dom_root->createCDATASection($value));
        } else {
            $child->appendChild($this->dom_root->createTextNode($value));
        }
    }

    public function saveXML() {
        $out = $this->dom_root->saveXML();
        //var_dump($out);
        return $out;
    }

}
