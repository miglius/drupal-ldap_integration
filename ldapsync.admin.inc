<?php
// $Id$

/**
 * @file
 * Module admin page callbacks.
 */

//////////////////////////////////////////////////////////////////////////////
// ldapsync settings

/**
 * Implements the settings page.
 *
 * @return
 *   The form structure.
 */
function ldapsync_admin_settings() {

  $form['options'] = array(
    '#type' => 'fieldset',
    '#title' => t('LDAPsync settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['options']['ldapsync_filter'] = array(
    '#type' => 'textfield',
    '#title' => t('Filter'),
    '#default_value' => variable_get('ldapsync_filter', ''),
    '#size' => 100,
    '#maxlength' => 255,
    '#description' => t('This will filter the LDAP query. See <a href="http://www.php.net/manual/en/function.ldap-search.php">PHP Help</a> for filter syntax. <br/ >example: (objectCategory=CN=Person,CN=Schema,CN=Configuration,DC=nwindenv,DC=com)'),
    '#required' => FALSE,
  );
  
  $options_time_interval = array(
    0 => t('As often as possible'),
    1*3600 => t('1 hour'),
    6*3600 => t('6 hours'),
    12*3600 => t('12 hours'),
    24*3600 => t('24 hours'),
    24*7*3600 => t('1 week'),
    -1 => t('never (only sync manually)')
  );
  $form['options']['ldapsync_time_interval'] = array(
    '#type' => 'select',
    '#title' => t('Sync time interval'),
    '#description' => t('The number of hours between each sync. A larger setting reduces server load for large LDAP directories.<br />You must have enabled cron, and ldapsync will not run more often than cron.'),
    '#default_value' => variable_get('ldapsync_time_interval', -1),
    '#options' => $options_time_interval,
  );
  $form['options']['ldapsync_missing_users_action'] = array(
    '#type' => 'select',
    '#title' => t('Missing users action'),
    '#description' => t("What to do when LDAP-authentified Drupal users don't exist or are disabled in LDAP. Choose block to disable the user accounts in Drupal. Choose warn to only log a warning to the Drupal log."),
    '#default_value' => variable_get('ldapsync_missing_users_action', 'warn'),
    '#options' => array(t('warn') => t('warn'), t('block') => t('block')),
  );

  $form['manual'] = array(
    '#type' => 'fieldset',
    '#title' => t('Manually sync users now'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['manual']['syncnow'] = array(
    '#type'  => 'submit',
    '#value' => t('Sync now'),
    '#submit' => array('ldapsync_admin_settings_sync_now'),
  );

  $form['submit'] = array(
    '#type'  => 'submit',
    '#value' => t('Save settings'),
  );

  return $form;
}

/**
 * Validate hook for the settings form.
 */
function ldapsync_admin_settings_validate($form, &$form_state) {
}

/**
 * Submit hook for the settings form.
 */
function ldapsync_admin_settings_submit($form, &$form_state) {
  $values = $form_state['values'];
  variable_set('ldapsync_time_interval', $values['ldapsync_time_interval']);
  variable_set('ldapsync_filter', $values['ldapsync_filter']);
  variable_set('ldapsync_missing_users_action', $values['ldapsync_missing_users_action']);
  drupal_set_message(t('Settings saved'));
}

/**
 * Syncs accounts.
 */
function ldapsync_admin_settings_sync_now() {
  $message = _ldapsync_sync();
  drupal_set_message(t($message));
}

