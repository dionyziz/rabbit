<?php
    /*
    Rabbit is Copyright (c) 2005 - 2007, Kamibu Development Group
    
    This source code file and all other source code files in Rabbit, 
    unless otherwise stated, are

    Copyright (c) 2005 - 2007, Kamibu Development Group.
    
    More information can be found at /etc/legal.txt.
    
    Please leave this notice only in index.php and do not
    paste it in other files of the source code repository.
    */

    global $page;
    
	require_once 'libs/rabbit/rabbit.php';
    
    Rabbit_Construct( 'HTML' );
    
    $req = $_GET;
    
    Rabbit_ClearPostGet();
    
    $page->AttachMainElement( 'main' , $req );
    $page->Output();

    Rabbit_Destruct();
?>
