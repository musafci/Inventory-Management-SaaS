<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use App\Services\ExpoPushService;
use App\Services\NotificationPreferenceService;
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

    public function handle(ExpoPushService $expoPushService, NotificationPreferenceService $preferenceService): void
    {
        setPermissionsTeamId($this->organizationId);

        $recipients = User::query()
            ->whereHas('organizations', function ($query): void {
                $query->where('organizations.id', $this->organizationId);
            })
            ->get();

        $eventKey = $this->orderType === 'sales_order'
            ? 'sales_order_status'
            : 'purchase_order_status';

        $recipients = $preferenceService->filterEnabledRecipients(
            $recipients,
            $this->organizationId,
            $eventKey,
        );

        if ($recipients->isEmpty()) {
            return;
        }

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

        $title = $this->orderType === 'sales_order' ? 'Sales order updated' : 'Purchase order updated';

        $expoPushService->sendToUsers(
            $recipients,
            $this->organizationId,
            $title,
            "{$this->orderNumber} is now {$this->newStatus}",
            [
                'organization_id' => $this->organizationId,
                'order_type' => $this->orderType,
                'order_id' => $this->orderId,
                'order_number' => $this->orderNumber,
                'new_status' => $this->newStatus,
            ],
        );

        Organization::query()->find($this->organizationId);
    }
}
