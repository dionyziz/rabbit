<?php
	/*
		Generalized OOP Database Layer
		Developer: dionyziz
	*/
	
	global $libs;
		
	$libs->Load( 'rabbit/db/drivers/base' );
	$libs->Load( 'rabbit/db/drivers/mysql' ); // load mysql support
	$libs->Load( 'rabbit/db/prepared' );
	$libs->Load( 'rabbit/db/table' );
	$libs->Load( 'rabbit/db/field' );
	$libs->Load( 'rabbit/db/index' );
	
	class DBException extends Exception {
	}
	
	class Database {
		protected $mAlias;
		protected $mDbName;
		protected $mHost;
		protected $mUsername;
		protected $mPassword;
		protected $mLink;
		protected $mCharSet;
		protected $mCharSetApplied;
		protected $mConnected;
		protected $mDriver;
		protected $mTables;
		
		public function __construct( $dbname = false, $driver = false ) {
			if ( $driver === false ) {
				$this->mDriver = New DBDriver_MySQL();
			}
			else {
				$this->mDriver = $driver;
			}
			w_assert( $this->mDriver instanceof DBDriver );
			w_assert( $dbname === false || is_string( $dbname ) ); // false because you can SwitchDb() later
			$this->mDbName = $dbname;
			$this->mConnected = false;
			$this->mCharSetApplied = true;
			$this->mCharSet = false;
			$this->mTables = array();
		}
		public function SetAlias( $value ) {
			$this->mAlias = $value;
		}
		public function SetCharset( $charset ) {
			if ( $this->mCharSet !== $charset ) {
				$this->mCharSet = $charset;
				$this->mCharSetApplied = false;
			}
		}
		public function Connect( $host = 'localhost' ) {
			$this->mHost = $host;
			
			return true;
		}
		public function Authenticate( $username , $password ) {
			$this->mUsername = $username;
			$this->mPassword = $password;
			
			return true;
		}
		public function Name() {
			return $this->mDbName;
		}
		public function Host() {
			return $this->mHost;
		}
		public function Port() {
			return $this->mPort;
		}
		public function Alias() {
			return $this->mAlias;
		}
		public function Equals( Database $target ) {
			return $this->Link() == $target->Link();
		}
		public function SwitchDb( $dbname ) {
			global $water;
			
			$this->mDbName = $dbname;
			if ( $this->mConnected ) {
				if ( $this->mDbName != '' ) {
					$selection = $this->mDriver->SelectDb( $this->mDbName, $this->mLink );
					if ( $selection === false ) {
						$water->Warning( "Failed to select the specified database:\n" . $this->mDriver->LastError( $this->mLink ) );
						return false;
					}
				}
			}
			return true;
		}
		protected function ActualConnect() {
			global $water;
			
			if ( !$this->mConnected ) {
				$this->mLink = $this->mDriver->Connect( $this->mHost , $this->mUsername , $this->mPassword , false );
				if ( $this->mLink === false ) {
					throw New DBException( "Connection to database failed:\n" . $this->mDriver->LastError( $this->mLink ) );
				}
				$this->mConnected = true;
				if ( empty( $this->mDbName ) ) {
					return true;
				}
				return $this->SwitchDb( $this->mDbName );
			}
			return false;
		}
		private function CharSetApply() {
			if ( !$this->mCharSetApplied ) {
				$this->mCharSetApplied = true;
				$this->Prepare( 'SET NAMES ' . $this->mCharSet )->Execute(); // TODO: this is only compatible with MySQL?
			}
		}
		public function Query( $sql ) {
			global $water;
			
			$this->ActualConnect(); // lazy connect
			if ( !$this->mConnected ) {
				$water->Warning( 'Could not execute SQL query because no SQL connection was found' , $sql );
				return false;
			}
			$this->CharSetApply();
			if ( $water->Enabled() ) {
				$backtrace = debug_backtrace();
				$lasttrace = array_shift( $backtrace );
				if ( strpos( $lasttrace[ 'file' ] , '/elements/' ) ) {
					$water->Warning( 'Potential database call from element!' );
				}
				if ( strpos( $lasttrace[ 'file' ] , '/units/' ) ) {
					$water->Warning( 'Potential database call from unit!' );
				}
			}
			$water->LogSQL( $sql );
			$res = $this->mDriver->Query( $sql , $this->mLink );
			$water->LogSQLEnd();
			if ( $res === false ) {
				// This leads to a Fatal Error: Wrong parameters for Exception( [string $exception [, long $code]] )
				// throw New Exception( 'Database query failed' , array( 'query' => $sql , 'error' => $this->mDriver->LastErrorNumber( $this->mLink ) . ': ' . $this->mDriver->LastError( $this->mLink ) ) );
				$water->Trace( 'SQL failed: ' . $this->mDriver->LastErrorNumber( $this->mLink ) . ': ' . $this->mDriver->LastError( $this->mLink ) . '; "' . $sql . '"' );
				throw New Exception( 'Database query failed: ' . $this->mDriver->LastErrorNumber( $this->mLink ) . ': ' . $this->mDriver->LastError( $this->mLink ) . '; "' . $sql . '"', $this->mDriver->LastErrorNumber( $this->mLink ) . ': ' . $this->mDriver->LastError( $this->mLink ) );
			}
			else if ( $res === true ) {
				return New DBChange( $this->mDriver, $this->mLink );
			}
			return New DBResource( $res, $this->mDriver );
		}
		public function Prepare( $rawsql ) {
			return New DBQuery( $rawsql, $this, $this->mDriver );
		}
		public function AttachTable( $alias, $actual ) {
			global $rabbit_settings;

			if ( !$rabbit_settings[ 'production' ] && !preg_match( '#^[\.a-zA-Z0-9_\-]+$#', $alias ) ) {
				throw New DBException( 'Invalid database table alias `' . $alias . '\'' );
			}
			$this->mTables[ $alias ] = New DBTable( $this, $actual, $alias );
		}
		public function DetachTable( $alias ) {
			if ( !preg_match( '#^[\.a-zA-Z0-9_\-]+$#', $alias ) ) {
				throw New DBException( 'Invalid database table alias `' . $alias . '\'' );
			}
			if ( !isset( $this->mTables[ $alias ] ) ) {
				throw New DBException( 'Cannot detach a table that has not been attached yet' );
			}
			unset( $this->mTables[ $alias ] );
		}
		public function TableByAlias( $alias ) {
			if ( !isset( $this->mTables[ $alias ] ) ) {
				return false;
			}
			return $this->mTables[ $alias ];
		}
		public function Tables() {
			return $this->mTables;
		}
		public function Link() {
			return $this->mLink;
		}
		public function ConstructField( DBField $field, $info ) {
			$this->mDriver->ConstructField( $field, $info );
		}
		public function __toString() {
			if ( !empty( $this->mDbName ) ) {
				return '`' . $this->mDbName . '`';
			}
			return '[database on ' . $this->mHost . ']';
		}
	}

	class DBChange {
		protected $mAffectedRows;
		protected $mInsertId;
		
		public function __construct( DBDriver $driver, $driver_link ) {
			$this->mAffectedRows = $driver->LastAffectedRows( $driver_link );
			$this->mInsertId = ( int )$driver->LastInsertId( $driver_link );
		}
		public function AffectedRows() {
			return $this->mAffectedRows;
		}
		public function Impact() {
			return $this->mAffectedRows > 0;
		}
		public function InsertId() {
			return $this->mInsertId;
		}
	}

	class DBResource {
		protected $mSQLResource;
		protected $mDriver;
		protected $mNumRows;
		protected $mNumFields;
		
		public function __construct( $sqlresource, DBDriver $driver ) {
			$this->mDriver = $driver;
			$this->mSQLResource = $sqlresource;
			$this->mNumRows = $this->mDriver->NumRows( $sqlresource );
			$this->mNumFields = $this->mDriver->NumFields( $sqlresource );
		}
		protected function SQLResource() {
			return $this->mSQLResource;
		}
		public function FetchArray() {
			return $this->mDriver->FetchAssociativeArray( $this->mSQLResource );
		}
		public function FetchField( $offset ) {
			return $this->mDriver->FetchField( $this->mSQLResource, $offset );
		}
		public function MakeArray() {
			$i = 0;
			$ret = array();
			while ( $row = $this->FetchArray() ) {
				foreach ( $row as $key => $value ) {
					$ret[ $i ][ $key ] = $value;
				}
				++$i;
			}
			
			return $ret;
		}
		public function ToObjectsArray( $class ) {
			$ret = array();
			while ( $row = $this->FetchArray() ) {
				$ret[] = New $class( $row ); // MAGIC!
			}

			return $ret;
		}
		public function NumRows() {
			return $this->mNumRows;
		}
		public function NumFields() {
			return $this->mNumFields;
		}
		public function Results() {
			return $this->NumRows() > 0;
		}
	}
?>
