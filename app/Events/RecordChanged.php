<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RecordChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $companyId,
        public readonly string $model,
        public readonly int    $modelId,
        public readonly string $action,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("company.{$this->companyId}")];
    }

    public function broadcastAs(): string
    {
        return 'record.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'model'    => $this->model,
            'model_id' => $this->modelId,
            'action'   => $this->action,
        ];
    }
}
