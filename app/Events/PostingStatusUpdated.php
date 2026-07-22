<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostingStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $companyId,
        public readonly string $entityType,  // asset_event | intangible_event | lease_event | inventory_movement
        public readonly int    $entityId,
        public readonly string $status,       // pending | posted | failed
        public readonly string $label,        // human-readable description
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("company.{$this->companyId}");
    }

    public function broadcastAs(): string
    {
        return 'posting.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'entity_type' => $this->entityType,
            'entity_id'   => $this->entityId,
            'status'      => $this->status,
            'label'       => $this->label,
        ];
    }
}
