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
  $baseTypes = array('Organization' => 1, 'Individual' => 1, 'Household' => 1);
  if (isset($baseTypes[$objectName]) && isset($config[$objectName])) {
    _relationshipgroupsync_contact_post($op, $objectName, $objectId, $objectRef, $config);
  }
  else if ($objectName == 'Group') {
    //TODO: Handle changes to groups
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
 * @param $newContact
 * @param $sourceCheck
 * @param $setup
 * @return array|int
 */
function _relationshipgroupsync_create_smart_group ($objectID, $newContact, $sourceCheck, $relType, $setup) {
  $config = _relationshipgroupsync_getConfig();
  //save the search
  $formValuesString = _relationshipgroupsync_buildFormValues($objectID, $relType, $setup['relationship_direction']);
  $savedSearch = new CRM_Contact_BAO_SavedSearch();
  $savedSearch->form_values = $formValuesString;

  $savedSearch->save();

  // also create a group that is associated with this saved search only if new saved search

  $title = ts('%1 (Related contacts)', array('1' => $newContact['display_name']));
  $params['title'] = $title;
  $params = array('group' => 'org.civicrm.relationshipgroupsync', 'name' => 'default_description');
  $description = civicrm_api3('Setting', 'getsingle', $params);
  $params['description'] = $description;


  // TODO: Allow description per setup?

  // TODO: Allow setting group type per setup
  /*
  if (isset($formValues['group_type']) &&
    is_array($formValues['group_type'])
  ) {
    $params['group_type'] = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR,
        array_keys($formValues['group_type'])
      ) . CRM_Core_DAO::VALUE_SEPARATOR;
  }
 */
  $params = array('group' => 'org.civicrm.relationshipgroupsync', 'name' => 'default_group_type');
  $groupType = civicrm_api3('Setting', 'getsingle', $params);
  $params['group_type'] = $groupType;
  $params['visibility'] = 'User and User Admin Only';
  $params['saved_search_id'] = $savedSearch->id;$params['saved_search_id'] = $savedSearch->id;
  $params['is_active'] = 1;
  $params['version'] = '3';
  $params['sequential'] = '0';

  $group_result = civicrm_api('Group', 'create', $params);
  return $group_result;
}


function _relationshipgroupsync_getConfig($activeOnly = TRUE) {
  // TODO: Test multiple domains
  $config = array();
  $params = array();
  $results = civicrm_api3('GroupSyncConfig', 'get', $params);
  if ($results['is_error'] == 0) {
    foreach ($results['values'] as $setup) {
      if (!$activeOnly || $setup['is_active']) {
        $config[$setup['contact_type']][$setup['contact_subtype']][$setup['relationship_type_id']]
          = array(
          'group_type' => $setup['group_type'],
          'relationship_direction' => $setup['relationship_direction'],
          'description' => $setup['description'],
        );
      }
    }
  }

  return $config;
}

function _relationshipgroupsync_buildFormValues($objectID, $relationship_type_id, $relationship_direction) {
  $relationshipSearch = $relationship_type_id . '_' . $relationship_direction;
  $params = array('group' => 'org.civicrm.relationshipgroupsync', 'name' => 'saved_search_template');
  $formValues1 = civicrm_api3('Setting', 'getsingle', $params);
  $formValues = unserialize($formValues1);
  $formValues['display_relationship_type'] = $relationshipSearch;
  $formValues['contact_id'] = $objectID;

  return serialize($formValues);
}

function _relationshipgroupsync_contact_post($op, $objectName, $objectId, $objectRef, $config) {
  if ($op == 'create') {

    // See if a group already exists - let's use source field for now
    // If not create a smart group
    dpm(array($objectId, $objectRef));
    $newContact = array();
    $newContact['id'] = $objectId;
    $newContact['display_name'] = $objectRef->display_name;
    $newContact['contact_sub_type'] = $objectRef->contact_sub_type == '' ? 'all' : $objectRef->contact_sub_type;

    $setups = $config[$objectName][$newContact['contact_sub_type']];

    foreach ($setups as $relationship_type_id => $setup) {

      $sourceCheck = _relationshipgroupsync_getgroupidentifier($newContact['id'],
        $newContact['contact_sub_type'], $relationship_type_id);
      $exists = _relationshipgroupsync_checkforexistinggroup($sourceCheck);
      if ($exists) {
        CRM_Core_Session::setStatus(ts('Unable to create Smart group. It already exists.', 'Relationship Sync'));
      }
      else {
        $result = _relationshipgroupsync_create_smart_group($objectId, $newContact, $sourceCheck, $relationship_type_id, $setup);
        //TODO: Allow user to specify a naming pattern for title

        dpm(array($result));
        if ($result['is_error'] == 1) {
          CRM_Core_Session::setStatus(ts('Unable to create Smart group', 'Relationship Sync'));
        }
      }
    }
  }
  else if ($op == 'edit') {
    // TODO: Update group(s) title if needed or deal with change to contact_sub_type
  }
  else if ($op == 'delete') {
    // TODO: Delete related group(s) if needed
  }
  else if ($op == 'trash') {
    // TODO: De-activate related group(s) if needed
  }
  else if ($op == 'restore') {
    // TODO: Activate related group(s) if needed
  }
}