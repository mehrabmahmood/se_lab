<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $rooms = [];  // Store connections by room (issue_id or 'lobby')

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        $roomId = $data['roomId'] ?? null;

        switch ($data['type']) {
            case 'join':
                $this->rooms[$roomId][$from->resourceId] = [
                    'conn' => $from,
                    'userType' => $data['userType'],
                    'userId' => $data['userId']
                ];
                echo "User {$data['userId']} ({$data['userType']}) joined room $roomId\n";
                break;

            case 'new-issue':
                // Broadcast to global 'lobby' for experts to see new issues
                foreach ($this->rooms['lobby'] ?? [] as $client) {
                    if ($client['userType'] === 'expert') {
                        $client['conn']->send(json_encode($data));
                    }
                }
                break;

            case 'chat':
            case 'call-request':
            case 'offer':
            case 'answer':
            case 'candidate':
            case 'call-accepted':
                // Broadcast to specific room (issue_id)
                foreach ($this->rooms[$roomId] ?? [] as $client) {
                    if ($client['conn'] !== $from) {
                        $client['conn']->send(json_encode($data));
                    }
                }
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        foreach ($this->rooms as $roomId => $room) {
            unset($this->rooms[$roomId][$conn->resourceId]);
        }
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}