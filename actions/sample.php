<?php
    function ActionSample( tString $example, tInteger $test ) {
        $example = $example->Get();
        $test    = $test->Get();
        
        // do something with $example / $test
        return Redirect();
    }
?>
