<?php

require 'Zend/Rest/Client.php';
require 'DataSync.class.php';

class SyncService
{
    /**
     * service specific data
     */
    protected $dataUrls;

    /**
    * Zend Rest Client
    */
    protected $client;

    protected $sync;

    public function __construct( $params )
    {
        $link = mysql_connect($params['db']['host'], $params['db']['user'], $params['db']['password']);
        
        mysql_select_db($params['db']['schema']);
        
        $this->sync = new DataSync($link, $params['service']['name']);

        $this->client = new Zend_Rest_Client( $params['service']['url'] );
        $this->client->getHttpClient()->setAuth($params['service']['token'], 'X', Zend_Http_Client::AUTH_BASIC);

        $this->dataUrls = $params['service']['streams'];
    }

    /**
     * process syncing, call this method by sub class object
     */
    public function doSyncing()
    {
        $this->sync->startSyncing();

        foreach ($this->dataUrls as $url => $count) {
            echo 'syncing ' . $url . '..' . "\n";

            $n = 0;

            while(true) {
                echo "offset $n.. ";

                $body = $this->client->restGet($url, array('n' => $n))->getBody();

                $xml = simplexml_load_string($body);

                if ($xml === false) {
                    break;
                }

                $nodeCount = count($xml);

                if ($nodeCount > 0) {
                    $this->sync->sync($xml);
                }

                $n += $nodeCount;

                if( $count == 0 || $nodeCount != $count) {
                    break;
                }

                echo "got $nodeCount results\n";
            }

            echo "synced\n";
        }

        $this->sync->endSyncing();
    }

}//eoc