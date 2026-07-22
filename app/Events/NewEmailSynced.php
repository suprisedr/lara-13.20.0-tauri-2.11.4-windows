<?php

namespace App\Events;

use App\Models\SupplierEmail;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewEmailSynced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $companyId,
        public readonly int $accountId,
        public readonly array $email,
    ) {}

    public static function fromSupplierEmail(SupplierEmail $email, int $companyId): self
    {
        return new self(
            companyId: $companyId,
            accountId: $email->company_email_account_id,
            email: [
                'id' => $email->id,
                'from_name' => $email->from_name,
                'from_email' => $email->from_email,
                'subject' => $email->subject,
                'status' => $email->status->value,
                'status_label' => $email->status->label(),
                'is_reviewed' => $email->is_reviewed,
                'received_at' => $email->received_at->format('d M Y'),
                'received_time' => $email->received_at->format('H:i'),
                'supplier_id' => $email->supplier_id,
                'supplier_name' => $email->supplier?->name,
                'ai_summary' => $email->ai_summary,
                'attachment_count' => $email->attachments()->count(),
            ],
        );
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("company.{$this->companyId}");
    }

    public function broadcastAs(): string
    {
        return 'email.synced';
    }

    public function broadcastWith(): array
    {
        return [
            'account_id' => $this->accountId,
            'email' => $this->email,
        ];
    }
}
