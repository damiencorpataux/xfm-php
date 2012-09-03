<?php

/**
 * Front controller class, RSS flavour.
 * @package xFreemwork
**/
class xRssFront extends xApiFront {

    var $xml_root_node = 'rss version="2.0"';

    var $params = array(
        'xformat' => 'xml'
    );
}