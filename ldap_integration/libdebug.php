<?php
// $Id$

function msg($string) {
  drupal_set_message("<pre style=\"border: 0; margin: 0; padding: 0;\">$string</pre>");
}

function msg_r($object) {
  msg(print_r($object, true));
}

?>
