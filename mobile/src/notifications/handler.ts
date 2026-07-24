import { router } from 'expo-router';
import * as Notifications from 'expo-notifications';
import { useEffect } from 'react';

type NotificationData = {
  organization_id?: number;
  stock_id?: number;
  product_id?: number;
  warehouse_id?: number;
  order_type?: string;
  order_id?: number;
};

export function useNotificationNavigation(): void {
  useEffect(() => {
    const subscription = Notifications.addNotificationResponseReceivedListener((response) => {
      const data = response.notification.request.content.data as NotificationData;

      if (data.order_id && data.order_type === 'sales_order') {
        router.push(`/(app)/sales-orders/${data.order_id}`);
        return;
      }

      if (data.order_id && data.order_type === 'purchase_order') {
        router.push(`/(app)/purchase-orders/${data.order_id}`);
        return;
      }

      if (data.product_id || data.stock_id) {
        router.push('/(app)/reports/low-stock');
      }
    });

    return () => {
      subscription.remove();
    };
  }, []);
}
