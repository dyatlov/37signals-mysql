<?php

class SyncHook
{
    /**
     * @param string $tableName
     * @param array $fields, each item is of format: fieldName => array('value' => 'field value', 'type' => 'field type')
     */
    public function beforeTableCreation($tableName, &$fields) {}

    /**
     * @param string $tableName
     * @param array $fields, each item is of format: fieldName => array('value' => 'field value', 'type' => 'field type')
     */
    public function afterTableCreation($tableName, &$fields) {}

    /**
     * @param string $tableName
     * @param array $fields, each item is of format: fieldName => array('value' => 'field value', 'type' => 'field type')
     */
    public function beforeDataAdding($tableName, &$fields) {}

    /**
     * @param string $tableName
     * @param array $fields, each item is of format: fieldName => array('value' => 'field value', 'type' => 'field type')
     */
    public function afterDataAdding($tableName, &$fields) {}

    /**
     * Use this function when you need to do something before sync started (like create database structure)
     */
    public function beforeSyncing() {}

    /**
     * Use this function after syncing has been done
     */
    public function afterSyncing() {}
}
