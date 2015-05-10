<?php namespace App\Services;

use WebSocket\ConnectionException;
use App\Services\HtmlBuilder;

class Client {
    private $client;

    public function __construct() {
        $this->client = new \WebSocket\Client("ws://".env('SOCKET_ADRESS').":".env('SOCKET_PORT'));
    }

    public function send($json) {
        try {
            $this->client->send($json);
        } catch (ConnectionException $e){}
    }

}