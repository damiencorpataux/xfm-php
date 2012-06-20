<?php
/*
 * (c) 2012 Damien Corpataux
 *
 * LICENSE
 * This library is licensed under the GNU GPL v3.0 license,
 * accessible at http://www.gnu.org/licenses/gpl-3.0.html
 *
**/

/**
 * Front controller class, rest flavour.
 * Deals with model output formatting.
 * @package xFreemwork
**/
class xModelFront extends xRestFront {

    protected function __construct($params = null) {
        parent::__construct($params);
    }

    function get() {
        $r = xModel::load(@$this->params['xmodel'], $this->params)->get();
        print $this->encode($r);
    }

    function post() {
        $r = xModel::load(@$this->params['xmodel'], $this->params)->post();
        print $this->encode($r);
    }

    function put() {
        $r = xModel::load(@$this->params['xmodel'], $this->params)->put();
        print $this->encode($r);
    }

    function delete() {
        $r = xModel::load(@$this->params['xmodel'], $this->params)->delete();
        print $this->encode($r);
    }
}