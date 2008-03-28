<?php
// $Id$

class LDAPInterface {

  function LDAPInterface() {
    $this->connection = NULL;
    //http://drupal.org/node/158671
    $this->server = NULL;
    $this->port = "389";
    $this->secretKey = NULL;
    $this->tls = false;
    $this->attr_filter = array('LDAPInterface', '__empty_attr_filter');
  }

  var $connection;
	var $server;
	var $port;
	var $tls;
	var $attr_filter;

  // This should be static, but that's not supported in PHP4
  function __empty_attr_filter($x) {
    return $x;
  }

  function setOption($option, $value) {
		switch($option) {
			case 'name':
				$this->name = $value;
				break;
			case 'server':
				$this->server = $value;
				break;
			case 'port':
				$this->port = $value;
				break;
			case 'tls':
				$this->tls = $value;
				break;
			case 'encrypted':
				$this->encrypted = $value;
				break;
			case 'user_attr':
				$this->user_attr = $value;
				break;
			case 'attr_filter':
				$this->attr_filter = $value;
				break;
			case 'basedn':
				$this->basedn = $value;
				break;
			case 'mail_attr':
			  $this->mail_attr = $value;
			  break;
		}
  }

  function getOption($option) {
		$ret = '';
		switch($option) {
			case 'version':
				$ret = -1;
				ldap_get_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, $ret);
				break;
			case 'name':
				$ret = $this->name;
				break;
			case 'port':
				$ret = $this->port;
				break;
			case 'tls':
				$ret = $this->tls;
				break;
			case 'encrypted':
				$ret = $this->encrypted;
				break;
			case 'user_attr':
				$ret = $this->user_attr;
				break;
			case 'attr_filter':
				$ret = $this->attr_filter;
				break;
			case 'basedn':
				$ret = $this->basedn;
				break;
			case 'mail_attr':
			  $ret = $this->mail_attr;
			  break;
		}
		return $ret;
  }

  function connect($dn = '', $pass = '') {
    $ret = FALSE;
	
    // If we're performing an account reset, we don't want to reconnect for each account, so skip the disconnect
    if ($this->reset) {
      if (!$this->connection) {
        if ($this->connectAndBind($dn, $pass)) {
          $ret = TRUE;
        }
      }
      else {
        $ret = TRUE;
      }
    }
    else {
      // http://drupal.org/node/164049
      // If a connection already exists, it should be terminated, don't try to reconnect
      $this->disconnect();

      if ($this->connectAndBind($dn, $pass)) {
        $ret = TRUE;
      }
    }

    return $ret;
  }

  function initConnection() {
    if (!$con = ldap_connect($this->server, $this->port)) {
      watchdog('user', 'LDAP Connect failure to ' . $this->server . ':' . $this->port);
      return NULL;
    }

    $this->connection = $con;
    ldap_set_option($con, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($con, LDAP_OPT_REFERRALS, 0);   
    // TLS encryption contributed by sfrancis@drupal.org
    if ($this->tls) {
      $vers = $this->getOption('version');
      if ($vers == -1) {
        watchdog('user', 'Could not get LDAP protocol version.');
      }

      if ($vers != 3) {
        watchdog('user', 'Could not start TLS, only supported by LDAP v3.');
      }
      else if (!function_exists('ldap_start_tls')) {
        watchdog('user', 'Could not start TLS. It does not seem to be supported by this PHP setup.');
      }
      else if (!ldap_start_tls($con)) {
        watchdog('user', t("Could not start TLS. (Error %errno: %error).", array('%errno' => ldap_errno($con), '%error' => ldap_error($con))));
      }
    }
  }

  function connectAndBind($dn = '', $pass = '') {
    $this->initConnection();

    $con = $this->connection;
    if (!$this->bind($dn, $pass)) {
      watchdog('user', t('LDAP Bind failure for user %user. Error %errno: %error', array('%user' => $dn,'%errno' => ldap_errno($con), '%error' => ldap_error($con))));
      return NULL;
    }

    return $con;
  }

  function bind($dn, $pass) {
    ob_start();
    set_error_handler(array('LDAPInterface', 'void_error_handler'));
    $ret = ldap_bind($this->connection, $dn, $pass);
    restore_error_handler();

    ob_end_clean();

    return $ret;
  }

  function disconnect() {
    if ($this->connection) {
      ldap_unbind($this->connection);
      $this->connection = NULL;
    }
  }

  function search($base_dn, $filter, $attributes = array()) {
    $ret = array();

    set_error_handler(array('LDAPInterface', 'void_error_handler'));
    $x = @ldap_search($this->connection, $base_dn, $filter, $attributes);
    restore_error_handler();

    if ($x && ldap_count_entries($this->connection, $x)) {
      $ret = ldap_get_entries($this->connection, $x);
    }
    return $ret;
  }

  // WARNING! WARNING! WARNING!
  // This function returns its entries with lowercase attribute names.
  // Don't blame me, blame PHP's own ldap_get_entries()
  function retrieveAttributes($dn) {
    set_error_handler(array('LDAPInterface', 'void_error_handler'));
    $result = ldap_read($this->connection, $dn, 'objectClass=*');
    $entries = ldap_get_entries($this->connection, $result);
    restore_error_handler();

    return call_user_func($this->attr_filter, $entries[0]);
  }

  function retrieveAttribute($dn, $attrname) {
    $entries = $this->retrieveAttributes($dn);
    return $entries[strtolower($attrname)][0];
  }

  function retrieveMultiAttribute($dn, $attrname) {
    $entries = $this->retrieveAttributes($dn);

    $result = array();
    $retrieved = $entries[strtolower($attrname)];
    $retrieved = $retrieved ? $retrieved : array();
    foreach ($retrieved as $key => $value) {
      if ($key !== 'count') {
        $result[] = $value;
      }
    }
    return $result;
  }

  function writeAttributes($dn, $attributes) {
    foreach ($attributes as $key => $cur_val) {
      if ($cur_val == '') {
        unset($attributes[$key]);
        $old_value = $this->retrieveAttribute($dn, $key);
        ldap_mod_del($this->connection, $dn, array($key => $old_value));
      }
      if (is_array ($cur_val)) {
        foreach ($cur_val as $mv_key => $mv_cur_val) {
          if ($mv_cur_val == '') {
            unset ($attributes[$key][$mv_key]);
          } else {
            $attributes[$key][$mv_key] = $mv_cur_val;
          }
        }
      }
    }

    ldap_modify($this->connection, $dn, $attributes);
  }

  // This should be static, but that's not supported in PHP4
  function void_error_handler($p1, $p2, $p3, $p4, $p5) {
    // Do nothing
  }
}

?>
