<?php
// $Id$

// Interesting constants that admins would want to mess with

//   The module automatically decides names for the Drupal roles
// based in the names of the LDAP groups. For example:
//   - LDAP group: Admins => Drupal role: Admins
//   - LDAP group: ou=Underlings,dc=myorg,dc=mytld => Drupal role: Underlings
//   However, if this is not enough, this name mapping can be refined
// by altering this array. Some examples are given.


$GLOBALS['ldap_group_role_mappings'] = array(
  // LDAP group => Drupal role
  'cn=users,ou=Group,dc=example,dc=com' => 'Users',
  'cn=IT,ou=Group,dc=example,dc=com' => 'SiteAdmins'  
);

// Note: Uncommenting this function will limit the groups -> roles conversion to ONLY those groups that are 
// specified in the function. 
/*
function ldapgroups_roles_filter($roles) { 
	global $ldap_group_role_mappings; 
	$newroles = array(); 
	// this should take the roles array, pass it thru the filters and send a NEW set of roles back 
	// the filter 
	foreach ( $roles as $role ) { 
		if ( array_search($role, $ldap_group_role_mappings) != FALSE ) { 
			// this role is specified -- grant 
			$newroles[] = $role; 
		}
	}
	return $newroles;;
}
*/


?>
