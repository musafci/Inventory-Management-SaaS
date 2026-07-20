<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification
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

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'organization_id' => $this->organizationId,
            'order_type' => $this->orderType,
            'order_id' => $this->orderId,
            'order_number' => $this->orderNumber,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
        ];
    }
}
