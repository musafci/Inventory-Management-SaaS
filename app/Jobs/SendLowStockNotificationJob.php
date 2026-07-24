<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\LowStockNotification;
use App\Services\ExpoPushService;
use App\Services\NotificationPreferenceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class SendLowStockNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * @var list<string>
     */
    private const MANAGER_PLUS_ROLES = ['Org Owner', 'Manager'];

    public function __construct(
        public int $organizationId,
        public int $stockId,
        public int $productId,
        public int $warehouseId,
        public int $quantityOnHand,
        public int $reorderPoint,
    ) {}

    public function handle(ExpoPushService $expoPushService, NotificationPreferenceService $preferenceService): void
    {
        if ($this->recentAlertExists()) {
            return;
        }

        setPermissionsTeamId($this->organizationId);

        $recipients = User::query()
            ->whereHas('organizations', function ($query): void {
                $query->where('organizations.id', $this->organizationId);
            })
            ->role(self::MANAGER_PLUS_ROLES)
            ->get();

        $recipients = $preferenceService->filterEnabledRecipients(
            $recipients,
            $this->organizationId,
            'low_stock',
        );

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = new LowStockNotification(
            organizationId: $this->organizationId,
            stockId: $this->stockId,
            productId: $this->productId,
            warehouseId: $this->warehouseId,
            quantityOnHand: $this->quantityOnHand,
            reorderPoint: $this->reorderPoint,
        );

        foreach ($recipients as $recipient) {
            $recipient->notify($notification);
        }

        $expoPushService->sendToUsers(
            $recipients,
            $this->organizationId,
            'Low stock alert',
            "Quantity on hand: {$this->quantityOnHand} (reorder at {$this->reorderPoint})",
            [
                'organization_id' => $this->organizationId,
                'stock_id' => $this->stockId,
                'product_id' => $this->productId,
                'warehouse_id' => $this->warehouseId,
            ],
        );
    }

    private function recentAlertExists(): bool
    {
        return DB::table('notifications')
            ->where('type', LowStockNotification::class)
            ->where('created_at', '>=', now()->subDay())
            ->where('data->product_id', $this->productId)
            ->exists();
    }
}
