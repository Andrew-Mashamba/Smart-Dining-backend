<?php

namespace App\Jobs;

use App\Services\FcmService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendFcmNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 5;

    /**
     * @param  string  $targetType  'staff', 'staff_members', or 'waiters'
     * @param  array  $targetIds  Staff IDs (ignored for 'waiters')
     * @param  array  $data  FCM data payload
     */
    public function __construct(
        public string $targetType,
        public array $targetIds,
        public array $data,
    ) {}

    public function handle(FcmService $fcmService): void
    {
        \Log::info('FCM Job: Processing', [
            'target_type' => $this->targetType,
            'target_ids' => $this->targetIds,
            'data_type' => $this->data['type'] ?? 'unknown',
            'order_id' => $this->data['order_id'] ?? null,
            'prep_status' => $this->data['prep_status'] ?? $this->data['new_status'] ?? null,
        ]);

        match ($this->targetType) {
            'staff' => $fcmService->sendToStaff($this->targetIds[0], $this->data),
            'staff_members' => $fcmService->sendToStaffMembers($this->targetIds, $this->data),
            'waiters' => $fcmService->sendToWaiters($this->data),
            default => \Log::warning("FCM Job: Unknown target type: {$this->targetType}"),
        };

        \Log::info('FCM Job: Completed', [
            'data_type' => $this->data['type'] ?? 'unknown',
            'order_id' => $this->data['order_id'] ?? null,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('FCM Job failed', [
            'target_type' => $this->targetType,
            'data_type' => $this->data['type'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);
    }
}
