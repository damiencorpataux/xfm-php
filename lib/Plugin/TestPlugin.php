<?php
/*
* (c) 2012 Damien Corpataux
*
* LICENSE
* This library is licensed under the GNU GPL v3.0 license,
* accessible at http://www.gnu.org/licenses/gpl-3.0.html
*
**/

class xTestPlugin extends xPlugin {

    function init() {
        xContext::$log->info(get_class($this)." init()");
    }
}
