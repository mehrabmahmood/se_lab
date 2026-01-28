<?php
require 'vendor/autoload.php';  // From Composer
require 'Chat.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8080  // Port for WebSocket
);

echo "WebSocket server running on port 8080...\n";
$server->run();