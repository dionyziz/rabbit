<?php
    function ElementDeveloperTest( tStringArray $testcases ) {
        global $libs;
        
        $libs->Load( 'rabbit/unittest' );
        $testcases = Test_GetTestcases()
        ?><ul><?php
        foreach ( $tests as $testcase ) {
            ?><li><?php
            echo $testcase->Name();
            ?></li><?php
        }
        ?></ul><?php
    }
?>
