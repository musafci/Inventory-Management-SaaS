import { Stack, useLocalSearchParams } from 'expo-router';
import { useMemo } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { ApiError } from '@/src/api/client';
import type { PurchaseOrder, PurchaseOrderItem } from '@/src/api/types';
import { useAuth } from '@/src/auth/AuthContext';
import { useInventoryLabels } from '@/src/hooks/useInventory';
import {
  useCancelPurchaseOrder,
  usePayPurchaseOrder,
  usePurchaseOrder,
  useReceivePurchaseOrder,
  useSendPurchaseOrder,
} from '@/src/hooks/useOrders';
import { canCreatePayment } from '@/src/permissions';

function formatStatus(status: string): string {
  return status.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function parseAmount(value: string | undefined): number {
  const parsed = Number.parseFloat(value ?? '0');
  return Number.isFinite(parsed) ? parsed : 0;
}

function isPurchaseOrderPayable(order: PurchaseOrder): boolean {
  return ['partially_received', 'received'].includes(order.status) && parseAmount(order.amount_due) > 0;
}

function productName(
  item: PurchaseOrderItem,
  labels: Map<number, string>,
): string {
  const nested = (item as PurchaseOrderItem & { product?: { name?: string } }).product?.name;
  return nested ?? labels.get(item.product_id) ?? `Product #${item.product_id}`;
}

export default function PurchaseOrderDetailScreen() {
  const { permissions } = useAuth();
  const { id } = useLocalSearchParams<{ id: string }>();
  const orderId = Number(id);
  const query = usePurchaseOrder(Number.isFinite(orderId) ? orderId : null);
  const labelsQuery = useInventoryLabels();
  const sendMutation = useSendPurchaseOrder();
  const cancelMutation = useCancelPurchaseOrder();
  const receiveMutation = useReceivePurchaseOrder();
  const payMutation = usePayPurchaseOrder();

  const productLabels = useMemo(
    () => labelsQuery.data?.products ?? new Map<number, string>(),
    [labelsQuery.data?.products],
  );
  const warehouseLabels = useMemo(
    () => labelsQuery.data?.warehouses ?? new Map<number, string>(),
    [labelsQuery.data?.warehouses],
  );

  const runAction = async (label: string, action: () => Promise<unknown>) => {
    try {
      await action();
      await query.refetch();
    } catch (error) {
      const message = error instanceof ApiError ? error.message : `${label} failed.`;
      Alert.alert('Action failed', message);
    }
  };

  if (query.isLoading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" />
      </View>
    );
  }

  if (!query.data) {
    return (
      <View style={styles.centered}>
        <Text style={styles.empty}>Purchase order not found.</Text>
      </View>
    );
  }

  const order = query.data;
  const firstItem = order.items?.[0];
  const isPending =
    sendMutation.isPending ||
    cancelMutation.isPending ||
    receiveMutation.isPending ||
    payMutation.isPending;

  const handleSend = () => {
    Alert.alert('Send order', 'Send this purchase order to the supplier?', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Send',
        onPress: () => {
          void runAction('Send', () => sendMutation.mutateAsync(order.id));
        },
      },
    ]);
  };

  const handleCancel = () => {
    Alert.alert('Cancel order', 'Cancel this purchase order?', [
      { text: 'Keep', style: 'cancel' },
      {
        text: 'Cancel order',
        style: 'destructive',
        onPress: () => {
          void runAction('Cancel', () => cancelMutation.mutateAsync(order.id));
        },
      },
    ]);
  };

  const handleReceive = () => {
    if (!firstItem || firstItem.quantity_remaining < 1) {
      Alert.alert('Receive', 'No remaining quantity to receive.');
      return;
    }

    Alert.alert('Receive stock', `Receive ${firstItem.quantity_remaining} units?`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Receive',
        onPress: () => {
          void runAction('Receive', () =>
            receiveMutation.mutateAsync({
              orderId: order.id,
              payload: {
                items: [
                  {
                    purchase_order_item_id: firstItem.id,
                    quantity: firstItem.quantity_remaining,
                  },
                ],
              },
            }),
          );
        },
      },
    ]);
  };

  const handlePay = () => {
    const amountDue = order.amount_due ?? '0';

    Alert.alert('Record payment', `Pay ${amountDue} in cash?`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Pay',
        onPress: () => {
          void runAction('Payment', () =>
            payMutation.mutateAsync({
              orderId: order.id,
              payload: {
                amount: amountDue,
                method: 'cash',
              },
            }),
          );
        },
      },
    ]);
  };

  return (
    <>
      <Stack.Screen options={{ title: order.po_number }} />

      <ScrollView
        contentContainerStyle={styles.container}
        refreshControl={(
          <RefreshControl
            refreshing={query.isRefetching}
            onRefresh={() => {
              void query.refetch();
            }}
          />
        )}>
        <View style={styles.statusRow}>
          <Text style={styles.statusBadge}>{formatStatus(order.status)}</Text>
          <Text style={styles.metaText}>Order date: {order.order_date}</Text>
        </View>

        <DetailRow label="Supplier" value={order.supplier?.name ?? `#${order.supplier_id}`} />
        <DetailRow label="Warehouse" value={warehouseLabels.get(order.warehouse_id) ?? `#${order.warehouse_id}`} />
        <DetailRow label="Total amount" value={order.total_amount} />
        <DetailRow label="Amount paid" value={order.amount_paid ?? '0.00'} />
        <DetailRow label="Amount due" value={order.amount_due ?? '0.00'} />

        {order.items && order.items.length > 0 ? (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Line items</Text>
            {order.items.map((item) => (
              <View key={item.id} style={styles.itemRow}>
                <Text style={styles.itemName}>{productName(item, productLabels)}</Text>
                <Text style={styles.itemMeta}>
                  Ordered {item.quantity_ordered} · Received {item.quantity_received} · Remaining{' '}
                  {item.quantity_remaining}
                </Text>
                <Text style={styles.itemMeta}>
                  Unit cost {item.unit_cost} · Subtotal {item.subtotal}
                </Text>
              </View>
            ))}
          </View>
        ) : null}

        <View style={styles.actions}>
          {order.status === 'draft' ? (
            <>
              <ActionButton
                disabled={isPending}
                label={sendMutation.isPending ? 'Sending…' : 'Send order'}
                onPress={handleSend}
              />
              <ActionButton
                disabled={isPending}
                label={cancelMutation.isPending ? 'Cancelling…' : 'Cancel order'}
                onPress={handleCancel}
                variant="danger"
              />
            </>
          ) : null}

          {['sent', 'partially_received'].includes(order.status) ? (
            <>
              <ActionButton
                disabled={isPending}
                label={receiveMutation.isPending ? 'Receiving…' : 'Receive remaining qty'}
                onPress={handleReceive}
              />
              {order.status === 'sent' ? (
                <ActionButton
                  disabled={isPending}
                  label={cancelMutation.isPending ? 'Cancelling…' : 'Cancel order'}
                  onPress={handleCancel}
                  variant="danger"
                />
              ) : null}
            </>
          ) : null}

          {isPurchaseOrderPayable(order) && canCreatePayment(permissions) ? (
            <ActionButton
              disabled={isPending}
              label={payMutation.isPending ? 'Processing…' : 'Pay (cash)'}
              onPress={handlePay}
            />
          ) : null}
        </View>
      </ScrollView>
    </>
  );
}

function DetailRow({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.row}>
      <Text style={styles.label}>{label}</Text>
      <Text style={styles.value}>{value}</Text>
    </View>
  );
}

function ActionButton({
  label,
  onPress,
  disabled,
  variant = 'primary',
}: {
  label: string;
  onPress: () => void;
  disabled?: boolean;
  variant?: 'primary' | 'danger';
}) {
  return (
    <Pressable
      disabled={disabled}
      onPress={onPress}
      style={[
        styles.actionButton,
        variant === 'danger' ? styles.actionButtonDanger : null,
        disabled ? styles.actionButtonDisabled : null,
      ]}>
      <Text
        style={[
          styles.actionButtonText,
          variant === 'danger' ? styles.actionButtonTextDanger : null,
        ]}>
        {label}
      </Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  centered: {
    alignItems: 'center',
    flex: 1,
    justifyContent: 'center',
    padding: 24,
  },
  empty: {
    color: '#64748b',
    fontSize: 15,
  },
  container: {
    padding: 16,
    paddingBottom: 40,
  },
  statusRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginBottom: 16,
  },
  statusBadge: {
    backgroundColor: '#dbeafe',
    borderRadius: 999,
    color: '#1d4ed8',
    fontSize: 13,
    fontWeight: '700',
    overflow: 'hidden',
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  metaText: {
    color: '#64748b',
    fontSize: 13,
  },
  row: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 10,
    borderWidth: 1,
    marginBottom: 10,
    padding: 14,
  },
  label: {
    color: '#64748b',
    fontSize: 13,
    marginBottom: 4,
  },
  value: {
    color: '#0f172a',
    fontSize: 16,
    fontWeight: '600',
  },
  section: {
    marginTop: 8,
  },
  sectionTitle: {
    color: '#0f172a',
    fontSize: 16,
    fontWeight: '700',
    marginBottom: 10,
  },
  itemRow: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 10,
    borderWidth: 1,
    marginBottom: 8,
    padding: 14,
  },
  itemName: {
    color: '#0f172a',
    fontSize: 15,
    fontWeight: '600',
  },
  itemMeta: {
    color: '#64748b',
    fontSize: 13,
    marginTop: 4,
  },
  actions: {
    gap: 10,
    marginTop: 20,
  },
  actionButton: {
    alignItems: 'center',
    backgroundColor: '#2563eb',
    borderRadius: 10,
    paddingVertical: 14,
  },
  actionButtonDanger: {
    backgroundColor: '#fee2e2',
  },
  actionButtonDisabled: {
    opacity: 0.6,
  },
  actionButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '700',
  },
  actionButtonTextDanger: {
    color: '#b91c1c',
  },
});
