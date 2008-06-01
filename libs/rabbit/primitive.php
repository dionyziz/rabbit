<?php
    /*
        Developer: Dionyziz

        This file should be included by default before anything else is done, as it sets up the core
        of Rabbit
    */
    
    global $rabbit_settings; // settings map
    // it is important that these singletons keeps their names, as they are used as-they-are in the code
	global $water;
    global $libs;
    global $elemental;
    global $page;
    
    Rabbit_ClearSuperGlobals();
    
    // you might want to modify this require to the fullpath to your settings file
    $rabbit_settings = require_once 'settings.php'; // site-wise settings
	
    // set the include path that will be used from now on, relative to the rootdir of our site
	set_include_path( get_include_path() . PATH_SEPARATOR . $rabbit_settings[ 'rootdir' ] );
    
    // load the debugging library
	$water = require_once 'libs/rabbit/water.php';
	$water->SetSetting( 'window_url'   , $rabbit_settings[ 'webaddress' ] . '/?p=debug' );
	$water->SetSetting( 'images_url'   , $rabbit_settings[ 'imagesurl' ] . 'water/' );
	$water->SetSetting( 'css_url'      , $rabbit_settings[ 'webaddress' ] . '/css/water.css' );
	$water->SetSetting( 'server_root'  , $rabbit_settings[ 'rootdir' ] );
    $water->SetSetting( 'calltracelvl' , 4 );
    
    w_assert( isset( $rabbit_settings[ 'rootdir' ] ), "`rootdir' setting is not defined" );
    w_assert( isset( $rabbit_settings[ 'applicationname' ] ), "`applicationname' setting is not defined" );
    if ( !isset( $rabbit_settings[ 'production' ] ) ) {
        // for security
        $rabbit_settings[ 'production' ] = true;
    }
    if ( !isset( $rabbit_settings[ 'timezone' ] ) ) {
        $rabbit_settings[ 'timezone' ] = 'GMT';
    }
    if ( isset( $rabbit_settings[ 'locale' ] ) ) {
        setlocale( LC_ALL, $rabbit_settings[ 'locale' ] );
    }

    // enable the debugging library if we're not on a production environment
	if ( !$rabbit_settings[ 'production' ] ) {
		$water->Enable(); // might be disabled later using ->Disable() if you wish
	}
    
    require_once 'libs/rabbit/mask.php';
    
    // load the libraries system -- it will be used to load everything else
    // the debugging module is NOT loaded using the libraries system, just
    // to make debugging the libraries system easier
	$libs = require_once 'libs/rabbit/lib.php';

    // load the helper libraries
    $libs->Load( 'rabbit/helpers/helpers' );
    
    if ( !function_exists( 'ValidURL' ) ) {
        throw New Exception( 'Could not load the Rabbit trivial libraries; check your "rootdir" setting in your settings.php file?' );
    }
    
    if ( strlen( $_SERVER[ 'REQUEST_METHOD' ] ) ) { // if we're running on a web environment
        w_assert( isset( $rabbit_settings[ 'webaddress' ] )    , "`webaddress' setting is not defined in a web environment" );
        w_assert( is_string( $rabbit_settings[ 'webaddress' ] ), "`webaddress' setting must be a string" );
        w_assert( isset( $rabbit_settings[ 'url' ] )           , "`url' setting is not defined in a web environment" );
        w_assert( is_string( $rabbit_settings[ 'url' ] )       , "`url' setting must be a string" );
        w_assert( isset( $rabbit_settings[ 'port' ] )          , "`port' setting not defined in a web environment" );
        w_assert( is_int( $rabbit_settings[ 'port' ] )         , "`port' setting must be an integer" );
        w_assert(    $rabbit_settings[ 'port' ] > 0 
                  && $rabbit_settings[ 'port' ] < 65536        , "`port' setting must be in range 1 - 65535" );
        
        // webaddress should point to a valid URL
        w_assert( ValidURL( $rabbit_settings[ 'webaddress' ] . '/' ) );
        
        // check if we're on the right site -- if not, redirect
        $httphost = $_SERVER[ 'HTTP_HOST' ];
        $httphost = explode( ':', $httphost );
        $httphost = strtolower( $httphost[ 0 ] );
        if ( $rabbit_settings[ 'hostname' ] !== false ) {
            w_assert( is_string( $rabbit_settings[ 'hostname' ] ) );
        	if ( $httphost != strtolower( $rabbit_settings[ 'hostname' ] ) ) {
        		header( 'HTTP/1.1 301 Moved Permanently' );
        		header( 'Location: ' . $rabbit_settings[ 'webaddress' ] . '/' );
        		exit();
        	}
        }
    }
    
    // define timezone, as of PHP 5.1.0
	if ( function_exists( 'date_default_timezone_set' ) ) {
		date_default_timezone_set( $rabbit_settings[ 'timezone' ] );
	}
	
	// manual register_globals off
	registerglobals_off(); 
	magicquotes_off();

    $libs->Load( 'rabbit/typesafety' );
    $libs->Load( 'rabbit/activerecord/satori' );
	$libs->Load( 'rabbit/db/db' );
    if ( isset( $rabbit_settings[ 'memcache' ] ) ) {
        $libs->Load( 'rabbit/memcache/mc' );
    }

    // set up databases
    if (    isset(    $rabbit_settings[ 'databases' ] ) 
         && is_array( $rabbit_settings[ 'databases' ] ) 
         && count(    $rabbit_settings[ 'databases' ] ) ) {
        foreach ( $rabbit_settings[ 'databases' ] as $dbname => $database ) {
            w_assert( substr( $dbname , 0 , 2 ) == 'db', 'Database alias "' . $dbname . '" must begin with "db"' );
            w_assert( isset( $database[ 'username' ] ), 'Database `username\' not specified for database alias $' . $dbname );
            w_assert( isset( $database[ 'password' ] ), 'Database `password\' not specified for database alias $' . $dbname );
            if ( !isset( $database[ 'hostname' ] ) ) {
                $database[ 'hostname' ] = 'localhost';
            }
            if ( !isset( $database[ 'charset' ] ) ) {
                $database[ 'charset' ] = 'DEFAULT';
            }
            if ( !isset( $database[ 'prefix' ] ) ) {
                $database[ 'prefix' ] = '';
            }
            
            if ( isset( $database[ 'driver' ] ) ) {
                w_assert( class_exists( 'DBDriver_' . $database[ 'driver' ] ), 'Database driver \'' . $database[ 'driver' ] . '\' used for database alias $' . $dbname . ' is invalid' );
                $drivername = 'DBDriver_' . $database[ 'driver' ];
                $driver = New $drivername(); // MAGIC
            }
            else {
                $driver = false;
            }
            if ( !isset( $database[ 'name' ] ) ){
                $database[ 'name' ] = false;
            }
            
            $GLOBALS[ $dbname ] = new Database( $database[ 'name' ], $driver );
            $GLOBALS[ $dbname ]->Connect( $database[ 'hostname' ] );
            $GLOBALS[ $dbname ]->Authenticate( $database[ 'username' ] , $database[ 'password' ] );
            $GLOBALS[ $dbname ]->SetCharset( $database[ 'charset' ] );
            
            foreach ( $database[ 'tables' ] as $alias => $tablename ) {
                if ( is_int( $alias ) ) {
                    $alias = $tablename;
                }
                $GLOBALS[ $dbname ]->AttachTable( $alias, $database[ 'prefix' ] . $tablename );
            }
        }
    }
    else {
        $water->Trace( 'No databases are specified in your settings file' );
    }
    
    // load the elements system
    $elemental = $libs->Load( 'rabbit/element' );
    $elemental->SetSetting( 'production' , $rabbit_settings[ 'production' ] );

    $libs->Load( 'rabbit/page/page' );
    $libs->Load( 'project' );

    if ( function_exists( 'Project_OnBeforeSessionStart' ) ) {
        Project_OnBeforeSessionStart();
    }
	session_start(); 
    registerglobals_off(); // this needs to be performed again now that session_start has been fired
    if ( function_exists( 'Project_OnAfterSessionStart' ) ) {
        Project_OnAfterSessionStart();
    }
?>
