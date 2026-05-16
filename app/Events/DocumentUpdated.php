<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $documentId;
    public $content;
    public $userId;
    public $userName;

    public function __construct($documentId, $content, $userId, $userName)
    {
        $this->documentId = $documentId;
        $this->content = $content;
        $this->userId = $userId;
        $this->userName = $userName;
    }

    public function broadcastOn()
    {
        return new Channel('document.' . $this->documentId);
    }

    public function broadcastAs()
    {
        return 'updated';
    }
}