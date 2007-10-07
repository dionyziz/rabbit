<?php
    $water = require '../libs/rabbit/water.php';
    require '../libs/rabbit/xml.php';
    
    $argv = $_SERVER[ 'argv' ];
    
    $i = 0;
    $force = false;
    foreach ( $argv as $arg ) {
        if ( $arg == '--force' ) {
            $force = true;
        }
        else {
            switch ( $i ) {
                case 0:
                    $type = $arg;
                    break;
                case 1:
                    $targetxml = $arg;
                    break;
                case 2:
                    $targetlib = $arg;
                    break;
            }
            ++$i;
        }
    }
    
    if ( !isset( $type ) || !isset( $xmlfile ) || !isset( $targetfile ) || $type !== 'lib' ) {
        die( "Usage: scaffold lib <xmldbfile> <libraryname>\n" );
    }
    
    $targetlib = strtolower( $targetlib );
    if ( !preg_match( '#^[a-z]([a-z0-9_]*)$#', $targetlib ) ) {
        echo "$targetlib: Library name is not legal";
        die();
    }
    
    $phpfile = 'libs/' . $targetlib . '.php';
    
    if ( !file_exists( $targetxml ) ) {
        echo "$targetxml: File does not exist";
        die();
    }
    if ( file_exists( $phpfile ) ) {
        if ( !$force ) {
            echo "$phpfile already exists; skipping. Use --force to override";
            die();
        }
    }
    
    $parser = New XMLParser( file_get_contents( $targetxml ) );
    $root = $parser->Parse();
    if ( $root === false ) {
        echo "$targetxml: File is not valid XML";
        die();
    }
    
    
    ob_start();
    echo "<?php\n\n";
    echo "\tclass " . ucfirst($targetlib) . " extends Satori {\n";
    echo "\t}\n";
    echo "?>\n";
    file_put_contents( $phpfile, ob_get_clean() );
?>
