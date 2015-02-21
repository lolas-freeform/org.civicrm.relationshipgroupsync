<?php

class CRM_Relationshipgroupsync_BAO_GroupSyncConfig extends CRM_Relationshipgroupsync_DAO_GroupSyncConfig {

  /**
   * Create a new GroupSyncConfig based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Relationshipgroupsync_DAO_GroupSyncConfig|NULL
   *
  public static function create($params) {
    $className = 'CRM_Relationshipgroupsync_DAO_GroupSyncConfig';
    $entityName = 'GroupSyncConfig';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  } */
}
