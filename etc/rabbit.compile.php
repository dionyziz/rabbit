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
			// echo 'Recursively copying ' . SOURCE . "$file to " . DESTINATION . "$file\n";
			$dest = escapeshellarg( DESTINATION . $file );
			$cmd = "svn merge $dest@HEAD " . escapeshellarg( SOURCE . $file ) . "@HEAD $dest";
			echo( "$cmd\n" );
			passthru( $cmd );
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
		// echo 'Copying ' . SOURCE . "$file to $target\n";
		$cmd = 'cp ' . escapeshellarg( SOURCE . $file ) . ' ' . escapeshellarg( $target );
		echo( "$cmd\n" );
		passthru( $cmd );
		passthru( 'svn add ' . escapeshellarg( DESTINATION . $file ) . ' --quiet' );
		++$i;
	}

	echo "$i sources copied\n";
?>
