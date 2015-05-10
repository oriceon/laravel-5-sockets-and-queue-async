<?php namespace App\Services;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Websocket implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $msg = json_decode($msg);

        echo "New message! ({$from->resourceId}) Channel: " . $msg->channel . " \n";

        switch ($msg->channel)
        {
            case 'auth':
                $user = Jwt::decode($msg->token);
                if ( ! array_key_exists($user->id, $this->clients))
                {
                    $this->clients[$user->id] = $from;
                }
                break;
            case 'toast':
                if (array_key_exists($msg->id, $this->clients))
                {
                    $this->clients[$msg->id]->send(json_encode(['channel' => 'toast', 'message' => $msg->message]));
                }
                break;
            case 'broadcast':
                foreach($this->clients as $index => $conn)
                {
                    $conn->send(json_encode(['channel' => 'toast', 'message' => $msg->message]));
                }
                break;
            case 'subscribe':
                $this->onSubscribe($msg->id, $msg->topic);
                break;
            case 'unsubscribe':
                $this->onUnSubscribe($from, $msg->topic);
                break;
            case 'publish':
                if (isset($this->topics[$msg->topic]))
                {
                    foreach ($this->topics[$msg->topic] as $id)
                    {
                        $conn = $this->clients[$id];
                        $msg  = $this->getPublish($msg, $id);
                        $conn->send(json_encode(['channel' => $msg->topic, 'message' => $msg->html]));
                    }
                }
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->removeConn($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $this->removeConn($conn);
        $conn->close();
    }

    private function removeConn(ConnectionInterface $conn){
        if(false !== $key = array_search($conn, $this->clients)){
            unset($this->clients[$key]);
        }

        if(false !== $key = array_search($conn, $this->connections)){
            unset($this->connections[$key]);
        }
    }
}