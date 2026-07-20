<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOrderStatusNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $organizationId,
        public string $orderType,
        public int $orderId,
        public string $orderNumber,
        public string $previousStatus,
        public string $newStatus,
    ) {}

    public function handle(): void
    {
        setPermissionsTeamId($this->organizationId);

        $recipients = User::query()
            ->whereHas('organizations', function ($query): void {
                $query->where('organizations.id', $this->organizationId);
            })
            ->get();

        $notification = new OrderStatusNotification(
            organizationId: $this->organizationId,
            orderType: $this->orderType,
            orderId: $this->orderId,
            orderNumber: $this->orderNumber,
            previousStatus: $this->previousStatus,
            newStatus: $this->newStatus,
        );

        foreach ($recipients as $user) {
            $user->notify($notification);
        }

        Organization::query()->find($this->organizationId);
    }
}
