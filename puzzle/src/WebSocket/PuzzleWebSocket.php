<?php
namespace WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class PuzzleWebSocket implements MessageComponentInterface {
    private $clients;
    private $puzzleRooms;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->puzzleRooms = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Yeni bağlantı! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        switch($data['type']) {
            case 'join':
                // Odaya katılma
                $puzzleId = $data['puzzleId'];
                if (!isset($this->puzzleRooms[$puzzleId])) {
                    $this->puzzleRooms[$puzzleId] = [];
                }
                $this->puzzleRooms[$puzzleId][$from->resourceId] = $from;
                
                // Mevcut puzzle durumunu yeni katılan kullanıcıya gönder
                $from->send(json_encode([
                    'type' => 'init',
                    'pieces' => isset($data['currentState']) ? $data['currentState'] : []
                ]));
                break;

            case 'move':
                // Parça hareketi
                $puzzleId = $data['puzzleId'];
                if (isset($this->puzzleRooms[$puzzleId])) {
                    foreach ($this->puzzleRooms[$puzzleId] as $client) {
                        if ($from !== $client) {
                            $client->send(json_encode([
                                'type' => 'move',
                                'pieceId' => $data['pieceId'],
                                'slotId' => $data['slotId']
                            ]));
                        }
                    }
                }
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        foreach ($this->puzzleRooms as $puzzleId => $room) {
            if (isset($room[$conn->resourceId])) {
                unset($this->puzzleRooms[$puzzleId][$conn->resourceId]);
            }
        }
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Hata: {$e->getMessage()}\n";
        $conn->close();
    }
} 