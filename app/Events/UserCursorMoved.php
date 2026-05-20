<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UserCursorMoved implements ShouldBroadcast
{
    public $documentId;
    public $userId;
    public $userName;
    public $userColor;
    public $mousePos;

    public function __construct($data)
    {
        $this->documentId = $data['documentId'];
        $this->userId = $data['userId'];
        $this->userName = $data['userName'];
        $this->userColor = $data['userColor'];
        $this->mousePos = $data['mousePos'];
    }

    public function broadcastOn()
    {
        return new Channel('document.' . $this->documentId);
    }

    public function broadcastAs()
    {
        return 'cursor-moved';
    }
}