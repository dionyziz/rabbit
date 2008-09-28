<?php
    function Project_Construct( $mode ) {
        global $rabbit_settings;
        global $libs;
        
        // add startup code here; this is called at the beginning of every script
        // load your basic libraries here
        // $libs->Load( 'mylib' );
        // you might also want to check for user authentication cookies,
        // or load certain application-specific settings
    }
    
    function Project_Destruct() {
        // add cleanup code here; this is called at the end of every script
    }
    
    function Project_PagesMap() {
        // routes to master elements
        return array(
            ""                     => "home",
            'example'           => 'mypage',
            'debug'             => 'developer/water',
            'unittest'          => 'developer/test/view'
        );
    }
?>
