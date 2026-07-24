import { router } from 'expo-router';
import * as Notifications from 'expo-notifications';
import { useEffect } from 'react';

type LowStockNotificationData = {
  organization_id?: number;
  stock_id?: number;
  product_id?: number;
  warehouse_id?: number;
};

export function useNotificationNavigation(): void {
  useEffect(() => {
    const subscription = Notifications.addNotificationResponseReceivedListener((response) => {
      const data = response.notification.request.content.data as LowStockNotificationData;

      if (data.product_id || data.stock_id) {
        router.push('/(app)/reports/low-stock');
      }
    });

    return () => {
      subscription.remove();
    };
  }, []);
}
