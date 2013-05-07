<?php

require 'SyncService.class.php';

$sync = new SyncService( array(
        'db' => array(
                'host' => 'localhost',
                'user' => 'root',
                'password' => '123',
                'schema' => 'highrise'
            ),
        'service' => array(
                'name' => 'highrise',
                'url' => 'https://xxx.highrisehq.com',
                'token' => 'abcdef1234567890abcdef1234567890',
                'streams' => array(
                        '/companies.xml' => 500,
                        '/people.xml' => 500,
                        '/kases.xml' => 500,
                        '/deals.xml' => 500,
                        '/tags.xml' => 0,
                        '/users.xml' => 0,
                        '/account.xml' => 0,
                    )
            ),
    ) );

$sync = new SyncService( array(
        'db' => array(
                'host' => 'localhost',
                'user' => 'root',
                'password' => '123',
                'schema' => 'basecamp'
            ),
        'service' => array(
                'name' => 'basecamp',
                'url' => 'https://xxx.basecamphq.com',
                'token' => 'abcdef1234567890abcdef1234567890',
                'streams' => array(
                        '/todo_lists.xml' => 500,
                        '/people.xml' => 500,
                        '/projects.xml' => 500,
                        '/account.xml' => 0,
                    )
            ),
    ) );

$sync->doSyncing();
