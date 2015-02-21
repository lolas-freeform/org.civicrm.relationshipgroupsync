<?php

require_once 'relationshipgroupsync.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function relationshipgroupsync_civicrm_config(&$config) {
  _relationshipgroupsync_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function relationshipgroupsync_civicrm_xmlMenu(&$files) {
  _relationshipgroupsync_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function relationshipgroupsync_civicrm_install() {
  _relationshipgroupsync_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function relationshipgroupsync_civicrm_uninstall() {
  _relationshipgroupsync_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function relationshipgroupsync_civicrm_enable() {
  _relationshipgroupsync_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function relationshipgroupsync_civicrm_disable() {
  _relationshipgroupsync_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function relationshipgroupsync_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _relationshipgroupsync_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function relationshipgroupsync_civicrm_managed(&$entities) {
  _relationshipgroupsync_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function relationshipgroupsync_civicrm_caseTypes(&$caseTypes) {
  _relationshipgroupsync_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function relationshipgroupsync_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _relationshipgroupsync_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
//TODO: Handle merge
/**
 * Implementation of hook_civicrm_post
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_post
 */
function relationshipgroupsync_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  $config = _relationshipgroupsync_getConfig();
  // TODO: Act on all contact types in config
  if ($objectName == 'Organization' && $op == 'create') {

    // See if a group already exists - let's use source field for now
    // If not create a smart group
    dpm(array($objectId, $objectRef));
    $organization = array();
    $organization['id'] = $objectId;
    $organization['display_name'] = $objectRef->display_name;
    $organization['contact_sub_type'] = $objectRef->contact_sub_type;

    foreach ($config['group_setup'] as $contact_type => $contact_sub_type) {
      foreach ($contact_sub_type as $subType => $setup) {
        $sourceCheck = _relationshipgroupsync_getgroupidentifier($organization['id'],
          $subType, $setup['relationship_type_id']);
        $exists = _relationshipgroupsync_checkforexistinggroup($sourceCheck);
        if ($exists) {
          CRM_Core_Session::setStatus(ts('Unable to create Smart group. It already exists.', 'Relationship Sync'));
        }
        else {
          $result = _relationshipgroupsync_create_smart_group($objectId, $organization, $sourceCheck, $setup);
          //TODO: Allow user to specify a naming pattern

          dpm(array($result));
          if ($result['is_error'] == 1) {
            CRM_Core_Session::setStatus(ts('Unable to create Smart group', 'Relationship Sync'));
          }
        }
      }
    }
  }
  else if ($objectName == 'Organization' && $op == 'edit') {
    // TODO: Update group(s) title if needed
  }
  else if ($objectName == 'Organization' && $op == 'delete') {
    // TODO: Delete related group(s) if needed
  }
  else if ($objectName == 'Organization' && $op == 'trash') {
    // TODO: De-activate related group(s) if needed
  }
  else if ($objectName == 'Organization' && $op == 'restore') {
    // TODO: Activate related group(s) if needed
  }
}


/**
 * @param $sourceCheck
 * @return array
 */
function _relationshipgroupsync_checkforexistinggroup($sourceCheck)
{
  $params = array('sequential' => 0, 'version' => 3);
  $params['source'] = $sourceCheck;
  $exist_results = civicrm_api('Group', 'get', $params);
  dpm($exist_results);
  $exists = 0;
  if ($exist_results['is_error'] == 0 && $exist_results['count'] >= 1) {
    foreach ($exist_results['values'] as $group_record) {
      if ($group_record['source'] == $sourceCheck) {
        $exists = $group_record['id'];
      }
    }
  }
  return $exists;
}

/**
 * @param $orgId, $subType, $relType
 * @return string
 */
function _relationshipgroupsync_getgroupidentifier($orgId, $subType, $relType) {
  return  "CRGS[{$orgId}_{$subType}]_{$relType}";
}

/**
 *
 * Copied in part from CRM/Contact/Form/Task/SaveSearch.php
 * @param $objectID
 * @param $objectRef
 * @param $sourceCheck
 * @param $setup
 * @return array|int
 */
function _relationshipgroupsync_create_smart_group ($objectID, $objectRef, $sourceCheck, $setup) {
  $config = _relationshipgroupsync_getConfig();
  //save the search
  $formValuesString = _relationshipgroupsync_buildFormValues($objectID, $setup);
  $savedSearch = new CRM_Contact_BAO_SavedSearch();
  $savedSearch->form_values = $formValuesString;

  $savedSearch->save();

  // also create a group that is associated with this saved search only if new saved search

  $title = ts('%1 (Related contacts)', array('1' => $objectRef['display_name']));
  $params['title'] = $title;
  $params['description'] = $config['default_description'];


  // TODO: Allow description?

  // TODO: Allow setting group type
  /*
  if (isset($formValues['group_type']) &&
    is_array($formValues['group_type'])
  ) {
    $params['group_type'] = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR,
        array_keys($formValues['group_type'])
      ) . CRM_Core_DAO::VALUE_SEPARATOR;
  }
  else {
    $params['group_type'] = '';
  } */
  $params['group_type'] =
  $params['visibility'] = 'User and User Admin Only';
  $params['saved_search_id'] = $savedSearch->id;$params['saved_search_id'] = $savedSearch->id;
  $params['is_active'] = 1;
  $params['version'] = '3';
  $params['sequential'] = '0';

  $group_result = civicrm_api('Group', 'create', $params);
  return $group_result;
}


function _relationshipgroupsync_getConfig() {
  // TODO: Support multiple domains
  $config = array();
  // Format is contact type
  $config['group_setup'] = array (
    'Organization' => array(
      '0' => array(
        'contact_sub_type' => 0,
        'relationship_type_id' => 5,
        'relationship_direction' => 'a_b',
        'group_type' => '',
      ),
    ),
  );
  $config['default_description'] = ts('Relationship Group Sync: Automatically generated group of related contacts.');
  return $config;
}

function _relationshipgroupsync_buildFormValues($objectID, $setup) {
  //TODO: Build array and serialize?
  $relationshipSearch = $setup['relationship_type_id'] . '_' . $setup['relationship_direction'];
  $formValues1 = 'a:41:{s:12:"hidden_basic";s:1:"1";s:12:"contact_type";a:0:{}s:5:"group";a:0:{}s:10:"group_type";a:0:{}s:21:"group_search_selected";s:5:"group";s:12:"contact_tags";a:0:{}s:9:"sort_name";s:0:"";s:5:"email";s:0:"";s:14:"contact_source";s:0:"";s:9:"job_title";s:0:"";s:10:"contact_id";s:3:"' . $objectID .
    '";s:19:"external_identifier";s:0:"";s:7:"uf_user";s:0:"";s:10:"tag_search";s:0:"";s:11:"uf_group_id";s:0:"";s:14:"component_mode";s:1:"7";s:8:"operator";s:3:"AND";s:25:"display_relationship_type";s:5:"' .
    $relationshipSearch . '";s:15:"privacy_options";a:0:{}s:16:"privacy_operator";s:2:"OR";s:14:"privacy_toggle";s:1:"1";s:13:"email_on_hold";a:1:{s:7:"on_hold";s:0:"";}s:30:"preferred_communication_method";a:5:{i:1;s:0:"";i:2;s:0:"";i:3;s:0:"";i:4;s:0:"";i:5;s:0:"";}s:18:"preferred_language";s:0:"";s:13:"phone_numeric";s:0:"";s:22:"phone_location_type_id";s:0:"";s:19:"phone_phone_type_id";s:0:"";s:19:"hidden_relationship";s:1:"1";s:16:"relation_type_id";s:0:"";s:20:"relation_target_name";s:0:"";s:15:"relation_status";s:1:"2";s:19:"relation_permission";s:1:"0";s:21:"relation_target_group";a:0:{}s:28:"relation_start_date_relative";s:0:"";s:23:"relation_start_date_low";s:0:"";s:24:"relation_start_date_high";s:0:"";s:26:"relation_end_date_relative";s:0:"";s:21:"relation_end_date_low";s:0:"";s:22:"relation_end_date_high";s:0:"";s:4:"task";s:2:"14";s:8:"radio_ts";s:6:"ts_all";}';
  $formValues = 'a:41:{s:12:"hidden_basic";s:1:"1";s:12:"contact_type";a:0:{}s:5:"group";a:0:{}s:10:"group_type";a:0:{}s:21:"group_search_selected";s:5:"group";s:12:"contact_tags";a:0:{}s:9:"sort_name";s:0:"";s:5:"email";s:0:"";s:14:"contact_source";s:0:"";s:9:"job_title";s:0:"";s:10:"contact_id";s:3:"170";s:19:"external_identifier";s:0:"";s:7:"uf_user";s:0:"";s:10:"tag_search";s:0:"";s:11:"uf_group_id";s:0:"";s:14:"component_mode";s:1:"7";s:8:"operator";s:3:"AND";s:25:"display_relationship_type";s:5:"5_a_b";s:15:"privacy_options";a:0:{}s:16:"privacy_operator";s:2:"OR";s:14:"privacy_toggle";s:1:"1";s:13:"email_on_hold";a:1:{s:7:"on_hold";s:0:"";}s:30:"preferred_communication_method";a:5:{i:1;s:0:"";i:2;s:0:"";i:3;s:0:"";i:4;s:0:"";i:5;s:0:"";}s:18:"preferred_language";s:0:"";s:13:"phone_numeric";s:0:"";s:22:"phone_location_type_id";s:0:"";s:19:"phone_phone_type_id";s:0:"";s:19:"hidden_relationship";s:1:"1";s:16:"relation_type_id";s:0:"";s:20:"relation_target_name";s:0:"";s:15:"relation_status";s:1:"2";s:19:"relation_permission";s:1:"0";s:21:"relation_target_group";a:0:{}s:28:"relation_start_date_relative";s:0:"";s:23:"relation_start_date_low";s:0:"";s:24:"relation_start_date_high";s:0:"";s:26:"relation_end_date_relative";s:0:"";s:21:"relation_end_date_low";s:0:"";s:22:"relation_end_date_high";s:0:"";s:4:"task";s:2:"14";s:8:"radio_ts";s:6:"ts_all";}';
  dpm(array($formValues1, $formValues));
  return $formValues1;
}