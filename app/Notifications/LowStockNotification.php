<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $organizationId,
        public int $stockId,
        public int $productId,
        public int $warehouseId,
        public int $quantityOnHand,
        public int $reorderPoint,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Low stock alert')
            ->line('A product has fallen at or below its reorder point.')
            ->line("Quantity on hand: {$this->quantityOnHand}")
            ->line("Reorder point: {$this->reorderPoint}")
            ->action('View inventory', url('/stocks'));
    }

    /**
     * @return array<string, int>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'organization_id' => $this->organizationId,
            'stock_id' => $this->stockId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'quantity_on_hand' => $this->quantityOnHand,
            'reorder_point' => $this->reorderPoint,
        ];
    }
}
