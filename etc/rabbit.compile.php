#!/usr/bin/php
<?php
    define( 'SOURCE', '/home/dionyziz/work/kamibu/excalibur/phoenix/' );
    define( 'DESTINATION', '/home/dionyziz/work/kamibu/rabbit/' );

    $files = explode( "\n", file_get_contents( 'rabbit.compile.lst' ) );
    
    $i = 0;
    
    foreach ( $files as $file ) {
        assert( is_string( $file ) );
        if ( empty( $file ) || substr( $file, 0, 1 ) != '/' ) {
            continue;
        }
        $file = substr( $file, 1 );
        if ( substr( $file, -1 ) == '~' ) {
            if ( !file_exists( DESTINATION . $file ) ) {
                echo "Warning: File $file does not exist in destination directory\n";
            }
            echo "Skipping: $file\n";
            continue;
        }
        if ( substr( $file, -2 ) == '/*' ) {
            $file = substr( $file, 0, -2 );
            // echo "Recursively copying " . SOURCE . $file . " to " . DESTINATION . $file . "\n";
            echo( 'svn merge ' . escapeshellarg( DESTINATION . $file ) . "@HEAD " . escapeshellarg( SOURCE . $file ) . "@HEAD " . escapeshellarg( DESTINATION . $file ) . "\n" );
            passthru( 'svn merge ' . escapeshellarg( DESTINATION . $file ) . "@HEAD " . escapeshellarg( SOURCE . $file ) . "@HEAD " . escapeshellarg( DESTINATION . $file ) );
            ++$i;
            continue;
        }
        if ( !file_exists( SOURCE . $file ) ) {
            echo "Warning: File $file does not exist in source location; skipping\n";
            continue;
        }
        if ( is_dir( SOURCE . $file ) ) {
            echo "Skipping directory: $file\n";
            continue;
        }
        $target = dirname( DESTINATION . $file );
        // echo "Copying " . SOURCE . $file . " to " . $target . "\n";
        echo( "cp " . escapeshellarg( SOURCE . $file ) . ' ' . escapeshellarg( $target ) . "\n" );
        passthru( "cp " . escapeshellarg( SOURCE . $file ) . ' ' . escapeshellarg( $target ) );
        passthru( "svn add " . escapeshellarg( DESTINATION . $file ) . " --quiet" );
        ++$i;
    }

    echo "$i sources copied\n";
?>
