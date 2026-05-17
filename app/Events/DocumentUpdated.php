<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $documentId;
    public $content;

    public function __construct($documentId, $content)
    {
        $this->documentId = $documentId;
        $this->content = $content;
    }

    public function broadcastOn()
    {
        // PAKAI PUBLIC CHANNEL
        return new Channel('document.' . $this->documentId);
    }

    public function broadcastAs()
    {
        return 'document-updated';
    }
}