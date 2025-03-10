<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use WebSocket\PuzzleWebSocket;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new PuzzleWebSocket()
        )
    ),
    8080
);

echo "WebSocket sunucusu başlatıldı...\n";
$server->run(); 