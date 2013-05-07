<?php

class DataSync
{
    protected $_xml = null;
    
    protected $_link = null;

    protected $_serviceType = null;

    protected $_serviceHook = null;

    protected $_createdTables = array();
    
    /**
     * @param resource $mysqlLink
     */
    public function __construct( $mysqlLink, $serviceType = null )
    {        
        $this->_link = $mysqlLink;
        $this->_serviceType = $serviceType;

        if(file_exists(dirname(__FILE__) . '/sync_hook_'.$this->_serviceType.'.php'))
        {
            include_once dirname(__FILE__) . '/sync_hook_'.$this->_serviceType.'.php';

            $className = 'SyncHook_' . ucfirst($this->_serviceType);

            $this->_serviceHook = new $className();
        }
        else
        {
            include_once dirname(__FILE__) . '/sync_hook.php';

            $this->_serviceHook = new SyncHook();
        }
    }

    public function startSyncing()
    {
        $this->_serviceHook->beforeSyncing();
    }

    public function endSyncing()
    {
        $this->_serviceHook->afterSyncing();
    }
    
    protected static function _fieldsSort($a, $b)
    {
        if( $a == $b )
        return 0;
    
        if( $a == 'id' )
        return -1;
        if( $b == 'id' )
        return 1;
    
        $aSub = substr($a, -3, 3);
        $bSub = substr($b, -3, 3);
    
        if($aSub == '-id' && $bSub != '-id')
        {
            return -1;
        }
    
        if($bSub == '-id' && $aSub != '-id')
        {
            return 1;
        }
    
        return 0;
    }
    
    protected function _createTable($tableName, &$fields, $uniq = array())
    {
        if( in_array( $tableName, $this->_createdTables) )
        {
            return;
        }

        $this->_serviceHook->beforeTableCreation($tableName, $fields);

        uksort($fields, array('DataSync', '_fieldsSort'));
    
        $fieldStrings = array();
    
        $indexStrings = array();
    
        foreach($fields as $key=>$field)
        {
            $str = '`' . $key . '` ';
    
            switch( $field['type'] )
            {
                case 'integer':
                    $str .= 'INT(11) NOT NULL DEFAULT 0';
                    if( $key == 'id' )
                    {
                        $str .= ' PRIMARY KEY';
                    }
                    else
                    {
                        $indexStrings[] = 'INDEX(`' . $key . '`)';
                    }
                    break;
                case 'datetime':
                    $str .= 'DATETIME DEFAULT "0000-00-00 00:00:00"';
                    break;
                default:
                    $isChar = true;
                foreach(array('value', 'description', 'text', 'body', 'message', 'data', 'blob', 'background') as $item)
                {
                    if($key == $item)
                    {
                        $isChar = false;
                        break;
                    }
                }
    
                if( !$isChar )
                {
                    $str .= 'TEXT';
                }
                else
                {
                    $str .= 'VARCHAR(255) DEFAULT ""';
                }
            }
    
            $fieldStrings[] = $str;
        }
    
        $sqlFields = implode(',', $fieldStrings);
    
        $indexes = rtrim( ',' . implode(',', $indexStrings), ',' );
        
        $unStrings = array();
        foreach($uniq as $un)
        {
            $unStrings[] = 'UNIQUE INDEX (`' . implode('`,`', $un) . '`)';
        }
        
        $uniqs = implode(',', $unStrings);
        $uniqs = rtrim( ',' . implode(',', $unStrings), ',' );

        mysql_query( "DROP TABLE IF EXISTS `$tableName`", $this->_link );
    
        $sql = <<<SQL
        CREATE TABLE `$tableName` (
        $sqlFields
        	$indexes
        	$uniqs
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8
SQL;
        
        mysql_query( $sql, $this->_link );

        $this->_serviceHook->afterTableCreation($tableName, $fields);

        $this->_createdTables[] = $tableName;
    }
    
    protected function _appendData($tableName, &$fields)
    {
        $this->_serviceHook->beforeDataAdding($tableName, $fields);

        $valueStrings = array();
        
        foreach($fields as $key=>$field)
        {
            $valueStrings[] = "`$key`='" . mysql_real_escape_string($field['value'], $this->_link) . "'";
        }
        
        $values = implode(',', $valueStrings);
        
        $sql = <<<SQL
        REPLACE INTO `$tableName`
        SET $values
SQL;
    
    	mysql_query( $sql, $this->_link );

        $this->_serviceHook->afterDataAdding($tableName, $fields);
    }
    
    protected function _recursive( $xml, &$stack, &$tables )
    {
        if( !count($xml->children()) )
        {
            return;
        }
    
        $hasFields = false;
        $fields = array();            
    
        $stackSet = false;
    
        if ( isset( $xml->id ) )
        {
            $stack[] = array( $xml->getName(), $xml->id . '' );
            $stackSet = true;
        }
    
        foreach( $xml->children() as $child )
        {
            if( count($child->children()) )
            {
                $this->_recursive( $child, $stack, $tables );
            }
            else
            {
                $type = 'string';
                $attrs = array();
    
                foreach($child->attributes() as $key=>$attr)
                {
                    $attrs[$key] = $attr . '';
                }
    
                if( isset( $attrs['type'] ) )
                {
                    $type = $attrs['type'];
                }
    
                if( $type != 'array' )
                {
                    $hasFields = true;
                    $fields[ $child->getName() ] = array( 'value' => $child . '', 'type' => $type );
                }
            }
        }
    
        if( $stackSet )
        {
            array_pop($stack);
        }
    
        if( $hasFields )
        {
            $tableName = $xml->getName();
    
            if( !isset( $tables[ $tableName ] ) )
            {
                $tables[ $tableName ] = true;
                $this->_createTable($tableName, $fields);             
            }
            
            foreach( $stack as $item )
            {
                if( !isset($fields['id']) )
                {
                    continue;
                }
                
                $conFields = array(
                    ($tableName . '-id') => array( 'value' => $fields['id']['value'], 'type' => 'integer' ),
                    ($item[0] . '-id') => array( 'value' => $item[1], 'type' => 'integer' )
                );
                
                $connectName = $tableName . '2' . $item[0];
                
                if( !isset( $tables[ $connectName ] ) )
                {      
                    $tables[ $connectName ] = true;
                    $this->_createTable($connectName, $conFields, array( array( ($tableName . '-id'), ($item[0] . '-id') ) ));
                }
                                
                $this->_appendData($connectName, $conFields);
            }
    
            $this->_appendData($tableName, $fields);
        }
    }    
    
    /**
     * @param string|SimpleXMLElement $xmlData
     * @throws Exception
     */
    public function sync( $xmlData )
    {
        if( $xmlData instanceof SimpleXMLElement )
        {
            $this->_xml = $xmlData;
        }
        else
        {
            $xml = simplexml_load_string( $xmlData );
            if( $xml !== false )
            {
                $this->_xml = $xml;
            }
        }
        
        if( $this->_xml != null )
        {
            $stack = array();
        
            $tables = array();
                
            $this->_recursive($this->_xml, $stack, $tables);
        }
        else
        {
            throw new Exception('Unable to parse XML');
        }
    }
}
