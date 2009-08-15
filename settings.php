<?php
    return array(
        'applicationname' => 'MyApp',
        'rootdir'         => '/var/www/localhost/httpdocs/myapp',
        'resourcesdir'    => '/home/resources',
        'imagesurl'       => 'http://example.org/myapp/images/',
        'production'      => false, // important: set this to true if you publish this application
        'hostname'        => 'example.org',
        'url'             => 'testapp',
        'port'            => 80,
        'webaddress'      => 'http://example.org/testapp',
        'timezone'        => 'UTC',
        'memcache'        => array(
                'type'     => 'dummy'
        ),
        'language'        => 'en',
        'databases'       => array( // prefix all keys with "db"
            'db' => array(
                'name'     => 'dbname', // change these according to your app
                'hostname' => 'localhost',
                'username' => 'dbusername',
                'password' => 'dbpassword',
                'charset'  => 'DEFAULT',
                'prefix'   => '',
                'tables'   => array(
                    'mytable'              => 'mytable', // fill in your database tables
                    'mytable2'          => 'mytable2',
                )
            )
        )
    );
?>
