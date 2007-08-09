<?php
    $water = require '../libs/rabbit/water.php';
    require '../libs/rabbit/xml.php';
    
    $argv = $_SERVER[ 'argv' ];
    
    if ( count( $argv ) != 3 || $argv[ 1 ] != 'lib' || empty( $argv[ 1 ] ) || empty( $argv[ 2 ] ) ) {
        echo "Usage: scaffold lib <xmldbfile> <targetfile>\n";
        die();
    }
    
    $xmlfile = $argv[ 2 ];
    $targetfile = strtolower( $argv[ 3 ] );
    
    $targetlib = basename( $targetfile, '.php' );
    
?>
