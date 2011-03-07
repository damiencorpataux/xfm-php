<?php
/*
 * (c) 2010 Damien Corpataux
 *
 * LICENSE
 * This library is licensed under the GNU GPL v3.0 license,
 * accessible at http://www.gnu.org/licenses/gpl-3.0.html
 *
**/

/**
 * Auth container interface.
 *
 * Session infos structure:
 * - username: username of the authenticted user
 * - roles: csv roles
 * @package xFreemwork
 */
class xAuth {

    function __constructor() {}

    /**
     * Sets auth information.
     * To be used upon successful authentication.
     * @param string Username
     * @param string Comma-separated roles names
     * @param array Additional user information
     */
    function set($username, $roles, $info = array()) {
        $_SESSION['x']['xAuth']['username'] = $username;
        $_SESSION['x']['xAuth']['roles'] = $roles;
        $_SESSION['x']['xAuth']['info'] = $info;
        $_SESSION['x']['xAuth']['logintime'] = mktime();
    }

    function clear() {
        unset($_SESSION['x']['xAuth']);
    }

    /**
     * Returns the authenticated user username.
     * @return string
     */
    function username() {
        return @$_SESSION['x']['xAuth']['username'];
    }

    /**
     * Returns an array containing the authenticated user roles.
     * @return array
     */
    function roles() {
        return explode(',', @$_SESSION['x']['xAuth']['roles']);
    }

    /**
     * Returns an array containing the authenticated user additional information.
     * If a key is given, returns the corresponding value, or null if key is invalid.
     * @param string Optional array key
     * @return array|scalar
     */
    function info($key = null) {
        if ($key) return @$_SESSION['x']['xAuth']['info'][$key];
        else return @$_SESSION['x']['xAuth']['info'];
    }

    /**
     * Returns the timestamp of the login point in time.
     * @return integer
     */
    function logintime() {
        return @$_SESSION['x']['xAuth']['logintime'];
    }

    /**
     * Returns true if the authenticated user has the given role(s).
     * @param string|array rolename(s) to check
     * @return bool
     */
    function is_role($rolenames) {
        $rolenames = xUtil::arrize($rolenames);
        $userroles = $this->roles();
        return array_intersect($rolenames, $userroles);
    }

    /**
     * Returns true if the authenticated username is the given username.
     * @param string rolename
     * @return bool
     */
    function is_user($username) {
        return $username === $this->username();
    }
}

?>