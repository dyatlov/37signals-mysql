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
                'url' => 'https://idibu.highrisehq.com',
                'token' => 'cf6a79fefd67629aa3a7de23a14f7e6d',
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
                'url' => 'https://idibu.basecamphq.com',
                'token' => '5253dd812b3276a8d5affe162544cfcac3621bd2',
                'streams' => array(
                        '/todo_lists.xml' => 500,
                        '/people.xml' => 500,
                        '/projects.xml' => 500,
                        '/account.xml' => 0,
                    )
            ),
    ) );

$sync->doSyncing();
