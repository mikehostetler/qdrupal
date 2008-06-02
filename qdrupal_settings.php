<?php
// $Id$

/**
 * Load all qcodo settings for a given application node. Just load one settings array if $name is passed in.
 */
function qdrupal_settings_load($nid,$name = '') {
  static $settings = array();

  if (!$settings) {
    $result = db_query('SELECT * FROM {qdrupal_setting} where nid = %d',$nid);
    while ($data = db_fetch_object($result)) {
      $data->setting = unserialize($data->setting);
      $settings[$data->name] = $data;
    }
  }
  return ($name ? $settings[$name] : $settings);
}

/**
 * Save a profile to the database.
 */
function qdrupal_settings_save($nid,$edit) {
  db_query("DELETE FROM {qdrupal_setting} WHERE nid = %d and (name = '%s' or name = '%s')",$nid, $edit['name'], $edit['old_name']);
  db_query("INSERT INTO {qdrupal_setting} (nid, name, setting) VALUES (%d,'%s', '%s')", $nid, $edit['name'], serialize($edit));
}

/**
 * Delete a database profile
 */
function qdrupal_settings_delete($nid,$name) {
  db_query("DELETE FROM {qdrupal_setting} WHERE nid = %d and name = '%s'",$nid, $name);
}
