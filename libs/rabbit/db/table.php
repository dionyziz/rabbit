<?php
    function DBTable_GetInfo( DBTable $table, $type = 'indexes' ) {
        global $mc;
        global $water;
        global $rabbit_settings;
        static $cache = false;

        $key = 'dbcache:' . $rabbit_settings[ 'dbschemaversion' ];
        w_assert( $table instanceof DBTable );
        $tablename = $table->Name;
        $tablealias = $table->Alias;
        $database = $table->Database;
        w_assert( $database instanceof Database );
        $databasealias = $database->Alias();
        if ( $cache === false ) {
            $cache = $mc->get( $key );
        }
        switch ( $type ) {
            case 'indexes':
                if ( !isset( $cache[ $databasealias ][ $tablename ][ 'indexes' ] ) ) {
                    $query = $database->Prepare(
                        'SHOW INDEX FROM :' . $tablealias . ';'
                    );
                    $query->BindTable( $tablealias );
                    $res = $query->Execute();
                    $indexinfos = array();
                    while ( $row = $res->FetchArray() ) {
                        if ( !isset( $indexinfos[ $row[ 'Key_name' ] ] ) ) {
                            $indexinfos[ $row[ 'Key_name' ] ] = array();
                        }
                        $indexinfos[ $row[ 'Key_name' ] ][] = $row;
                    }
                    $cache[ $databasealias ][ $tablename ][ 'indexes' ] = $indexinfos;
                    $mc->set( $key, $cache );
                }
                $indexinfos = $cache[ $databasealias ][ $tablename ][ 'indexes' ];
                $indexes = array();
                foreach ( $indexinfos as $indexinfo ) {
                    $indexes[] = New DBIndex( $table, $indexinfo );
                }
                return $indexes;
            case 'fields':
                if ( !isset( $cache[ $databasealias ][ $tablename ][ 'fields' ] ) ) {
                    $query = $database->Prepare(
                        'SHOW FIELDS FROM :' . $tablealias . ';'
                    );
                    $query->BindTable( $tablealias );
                    $res = $query->Execute();
                    $fieldinfos = array();
                    while ( $row = $res->FetchArray() ) {
                        $fieldinfos[] = $row;
                    }
                    $cache[ $databasealias ][ $tablename ][ 'fields' ] = $fieldinfos;
                    $mc->set( $key, $cache );
                }
                $fieldinfos = $cache[ $databasealias ][ $tablename ][ 'fields' ];
                $fields = array();
                foreach ( $fieldinfos as $fieldinfo ) {
                    $fields[ $fieldinfo[ 'Field' ] ] = New DBField( $table, $fieldinfo );
                }
                return $fields; 
        }
    }

    class DBTable {
        protected $mDb;
        protected $mTableName;
        protected $mAlias;
        protected $mFields;
        protected $mIndexes;
        protected $mExists;
        
        public function InsertInto( $inserts, $ignore = false, $delayed = false, $quota = 500 ) {
            // $insert = array( field1 => value1 , field2 => value2 , ... );
            // ->Insert( $insert );
            // ->Insert( array( $insert1 , $insert2 , ... ) );
            
            w_assert( !( $ignore && $delayed ) );
            w_assert( $this->Exists() ); // cannot insert into a non-existing table
            
            // assert at least one insert statement; or at least one field
            w_assert( count( $inserts ) );
            // if doing only one direct insert, call self with that special case;
            // keep in mind that in single inserts, the values of the array must be scalar
            // while in multiple inserts, the values are arrays of scalar values
            if ( !is_array( end( $inserts ) ) ) {
                $inserts = array( $inserts );
                $multipleinserts = false;
            }
            else {
                $multipleinserts = true;
            }
            
            if ( $ignore ) {
                $insertinto = 'INSERT IGNORE INTO';
            }
            else if ( $delayed ) {
                $insertinto = 'INSERT DELAYED INTO';
            }
            else {
                $insertinto = 'INSERT INTO';
            }
            
            // get last insert to get the fields of the insert
            $lastinsert = end( $inserts );
            $fields = array();
            // build fields list (only once)
            foreach ( $lastinsert as $field => $value ) {
                // assert correct field names
                w_assert( preg_match( '/^[a-zA-Z0-9_\-]+$/' , $field ) );
                $fields[] = $field;
            }
            // assert there is at least one field
            w_assert( count( $fields ) );
            // return value will be an array of change structures
            $changes = array();
            // split insert into 500's, for speed and robustness; this also limits the chance of getting out of query
            // size bounds
            for ( $i = 0 ; $i < count( $inserts ) ; $i += $quota ) {
                $partinserts = array_slice( $inserts , $i , $quota );
                w_assert( count( $partinserts ) );
                $insertvalues = array();
                foreach ( $partinserts as $insert ) {
                    reset( $fields );
                    foreach ( $insert as $field => $value ) {
                        // assert the fields are the same number and in the same order in each insert
                        $thisfield = each( $fields );
                        w_assert( $thisfield[ 'value' ] == $field );
                    }
                    $insertvalues[] = $insert;
                }
                w_assert( count( $insertvalues ) );
                $bindings = array();
                $i = 0;
                foreach ( $insertvalues as $valuetuple ) {
                    $bindings[] = ':insert' . $i;
                    ++$i;
                }
                
                $query = $this->mDb->Prepare(
                    "$insertinto
                        :" . $this->mAlias . "
                    (`" . implode( '`, `' , $fields ) . "`) VALUES
                    " . implode( ',', $bindings ) . ";"
                ); // implode all value lists into (list1), (list2), ...
                $i = 0;
                foreach ( $insertvalues as $valuestuple ) {
                    $query->Bind( substr( $bindings[ $i ], 1 ), $valuestuple );
                    ++$i;
                }
                $query->BindTable( $this->mAlias );
                $changes[] = $query->Execute();
            }
            if ( !$multipleinserts ) {
                return end( $changes ); // only return the one and only single item of $changes
            }
            return $changes; // return an array of change
        }
        public function Equals( DBTable $target ) {
            if (    $this->Exists() != $target->Exists()
                 || $this->Name != $target->Name
                 || $this->Alias != $target->Alias ) {
                return false;
            }
            if ( $this->mDb !== false ) {
                if ( $target->mDb === false ) {
                    return false;
                }
                return $this->mDb->Equals( $target->Database );
            }
            return true;
        }
        public function FieldByName( $name ) {
            $this->Fields;
            if ( !isset( $this->mFields[ $name ] ) ) {
                return false;
            }
            return $this->mFields[ $name ];
        }
        public function __get( $key ) {
            switch ( $key ) {
                case 'Name':
                    return $this->mTableName;
                case 'Alias':
                    return $this->mAlias;
                case 'Fields':
                case 'Indexes':
                    $attribute = 'm' . $key;
                    if ( $this->$attribute === false ) {
                        $this->$attribute = DbTable_GetInfo( $this, strtolower( $key ) );
                    }
                    return $this->$attribute;
                case 'Database':
                    return $this->mDb;
            }
        }
        public function __set( $key, $value ) {
            switch ( $key ) {
                case 'Name':
                    w_assert( is_string( $value ), "Table name should be a string" );
                    $this->mTableName = $value;
                    break;
                case 'Alias':
                    $this->mAlias = $value;
                    break;
                case 'Database':
                    w_assert( is_object( $value ) );
                    w_assert( $value instanceof Database );
                    $this->mDb = $value;
                    break;
            }
        }
        public function __construct( $db = false, $tablename = false, $alias = '' ) {
            $this->mExists = false;
            
            if ( $db !== false ) {
                w_assert( is_object( $db ) );
                w_assert( $db instanceof Database );
                $this->mDb = $db;
            }
            else {
                $this->mDb = false;
            }
            
            if ( $tablename === false ) {
                // new table
                w_assert( $alias === '', 'No aliases should be passed for new DB tables' );
                $this->mFields = array(); // no fields defined yet
            }
            else {
                // existing table
                w_assert( is_string( $alias ), 'Database table alias `' . $alias . '\' is not a string' );
                w_assert( is_string( $tablename ), 'Database table name `' . $tablename . '\' is not a string' );
                w_assert( preg_match( '#^[\.a-zA-Z0-9_\-]*$#', $alias ), 'Database table alias `' . $alias . '\' is invalid' );
                w_assert( preg_match( '#^[\.a-zA-Z0-9_\-]+$#', $tablename ), 'Database table name `' . $tablename . '\' is invalid' );
                $this->mTableName = $tablename;
                $this->mExists = true;
                $this->mFields = false; // not retrieved yet
            }
            $this->mAlias = $alias;
            $this->mIndexes = false;
        }
        public function Copy( $newtable, $newalias ) {
            $this->mDb->AttachTable( $newalias, $newtable );

            $query = $this->mDb->Prepare( 'CREATE TABLE :' . $newalias . ' LIKE :' . $this->mAlias . ';' );
            $query->BindTable( $newalias );
            $query->BindTable( $this->mAlias );
            $query->Execute();
        }
        public function Truncate() {
            $query = $this->mDb->Prepare( 'TRUNCATE :' . $this->mAlias . ';' );
            $query->BindTable( $this->mAlias );
            return $query->Execute();
        }
        public function CreateField( /* $field1, $field2, ... */ ) {
            $fields = func_get_args();
            w_assert( count( $fields ) );
            foreach ( $fields as $field ) {
                w_assert( $field instanceof DBField );
                $this->mFields[ $field->Name ] = $field;
            }
        }
        public function CreateIndex( /* $indexe1, $index2, ... */ ) {
            $indexes = func_get_args();
            w_assert( count( $indexes ) );
            foreach ( $indexes as $index ) {
                w_assert( $index instanceof DBIndex );
                $this->mIndexes[] = $index;
            }
        }
        public function Save() {
            w_assert( !$this->Exists(), 'Cannot create database table; the table already exists' );
            w_assert( !empty( $this->mAlias ), 'You must set an alias when saving a database table, in order to make it accessible through binding' );
            w_assert( !empty( $this->mTableName ), 'You cannot create a database table without a name' );
            w_assert( $this->mDb instanceof Database );
            
            $this->mDb->AttachTable( $this->mAlias, $this->mTableName );
            
            $first = true;
            $fieldsql = array();
            foreach ( $this->mFields as $field ) {
                $field->Exists = true;
                $field->ParentTable = $this;
                $fieldsql[] = $field->SQL;
            }
            $indexsql = array();
            foreach ( $this->mIndexes as $index ) {
                $index->Exists = true;
                $index->ParentTable = $this;
                $indexsql[] = $index->SQL;
            }
            $query = $this->mDb->Prepare( 
                "CREATE TABLE :" . $this->mAlias . " ( "
                . implode( ', ', array_merge( $fieldsql, $indexsql ) )
                . " );"
            );
            $query->BindTable( $this->mAlias );
            $query->Execute();
            $this->mExists = true;
        }
        public function Delete() {
            $query = $this->mDb->Prepare( "DROP TABLE :" . $this->mAlias . ";" );
            $query->BindTable( $this->mAlias );
            $query->Execute();
            $this->mExists = false;
        }
        public function Exists() {
            return $this->mExists;
        }
        public function __toString() {
            return ( string )$this->mDb . '.`' . $this->mTableName . '`';
        }
    }
?>
