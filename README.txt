ldap_integration.module for Drupal 4.5
--------------------------------------

status: STABLE

 - BRIEFING

  This module allows users to authenticate against an admin-defined LDAP
directory, as well as perform searches on its entries.

 - INSTALLATION

  Just add the module file (ldap_integration.module) to the /modules/
directory of your Drupal 4.5 installation.

 - SETUP

  Prior to its use, administrators must set some variables in the preferences
page, located at /admin/settings/ldap_integration.

  Parameters should be quite straightforward to set, except those called
'LDAP login pattern' and 'LDAP login replacement' which refer to the way
Drupal converts Drupal logins into LDAP logins, via regular expressions.
Further explanation follows.

  In Drupal 4.5, logins to external authentification sources (not Drupal's
own database) must be in the form jdoe@foo.bar. This means that every login
through this module must have that form.
  On the other hand, logins to LDAP directories are in the form of a DN,
i.e. something similar to: uid=jdoe,dc=foo,dc=bar .

  So, the admin must set the way Drupal logins convert into LDAP logins. This
os defined through the 'LDAP login pattern' and 'LDAP login replacement'
parameters in preferences.

  For help on defining these parameters, refer to the relevant entry on the
PHP manual: http://www.php.net/manual/en/function.preg-replace.php

 - USE

  The authentication facility needs no further configuration. The search
feature, however, needs enabling the block provided by the module in
the /admin/block administration page.

 - ISSUES

  For the search feature to work, user's LDAP password needs to be stored
in the database as part of the session data. This should not be a problem,
since the database is meant to be properly secured.

  If the mcrypt module is enabled in the PHP installation, the password
will not be saved as cleartext, but encrypted. The key for this encryption
can be changed by editing the module file (ldap_integration.module). The
constant defining it is called LDAP_PASSWORDS_SECRET_KEY and is found
at the beggining of the file.

 - CREDITS

  Author: Pablo Brasero Moreno <pablobm@gmail.com> (http://pablobm.com)
  Based upon the original code by Moshe Weitzman <weitzman@tejasa.com>
