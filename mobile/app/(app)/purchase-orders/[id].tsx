import { Link, Stack, useLocalSearchParams } from 'expo-router';
import { useMemo, useState } from 'react';
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

import { LineItemsActionModal, type LineItemRow } from '@/components/LineItemsActionModal';
import { PaymentActionModal } from '@/components/PaymentActionModal';
import { ApiError } from '@/src/api/client';
import type { PaymentMethod, PurchaseOrder, PurchaseOrderItem } from '@/src/api/types';
import { useAuth } from '@/src/auth/AuthContext';
import { shareOrderPrintHtml } from '@/src/utils/shareOrderPrint';
import { useInventoryLabels } from '@/src/hooks/useInventory';
import {
  useCancelPurchaseOrder,
  usePayPurchaseOrder,
  usePurchaseOrder,
  useReceivePurchaseOrder,
  useSendPurchaseOrder,
} from '@/src/hooks/useOrders';
import { canCreatePayment, canUpdatePurchaseOrder } from '@/src/permissions';

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
  const { permissions, organizationId } = useAuth();
  const { id } = useLocalSearchParams<{ id: string }>();
  const orderId = Number(id);
  const query = usePurchaseOrder(Number.isFinite(orderId) ? orderId : null);
  const labelsQuery = useInventoryLabels();
  const sendMutation = useSendPurchaseOrder();
  const cancelMutation = useCancelPurchaseOrder();
  const receiveMutation = useReceivePurchaseOrder();
  const payMutation = usePayPurchaseOrder();

  const [receiveVisible, setReceiveVisible] = useState(false);
  const [receiveItems, setReceiveItems] = useState<LineItemRow[]>([]);
  const [receiveNote, setReceiveNote] = useState('');
  const [payVisible, setPayVisible] = useState(false);
  const [payAmount, setPayAmount] = useState('');
  const [payMethod, setPayMethod] = useState<PaymentMethod>('bank_transfer');
  const [payReference, setPayReference] = useState('');
  const [payNote, setPayNote] = useState('');

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
      const result = await action();

      if (result === null) {
        Alert.alert('Queued offline', `${label} will run when you reconnect.`);
        return;
      }

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
    const items = (order.items ?? [])
      .filter((item) => item.quantity_remaining > 0)
      .map((item) => ({
        id: item.id,
        label: productName(item, productLabels),
        maxQuantity: item.quantity_remaining,
        quantity: String(item.quantity_remaining),
      }));

    if (items.length === 0) {
      Alert.alert('Receive', 'No remaining quantity to receive.');
      return;
    }

    setReceiveItems(items);
    setReceiveNote('');
    setReceiveVisible(true);
  };

  const submitReceive = () => {
    const payloadItems = receiveItems
      .map((item) => ({
        purchase_order_item_id: item.id,
        quantity: Number.parseInt(item.quantity, 10),
      }))
      .filter((item) => Number.isFinite(item.quantity) && item.quantity > 0);

    if (payloadItems.length === 0) {
      Alert.alert('Validation', 'Enter at least one valid quantity.');
      return;
    }

    void runAction('Receive', async () => {
      const result = await receiveMutation.mutateAsync({
        orderId: order.id,
        payload: {
          items: payloadItems,
          note: receiveNote.trim() || null,
        },
      });
      setReceiveVisible(false);
      return result;
    });
  };

  const handlePay = () => {
    setPayAmount(order.amount_due ?? '0');
    setPayMethod('bank_transfer');
    setPayReference('');
    setPayNote('');
    setPayVisible(true);
  };

  const submitPay = () => {
    const parsedAmount = Number.parseFloat(payAmount);
    if (!Number.isFinite(parsedAmount) || parsedAmount <= 0) {
      Alert.alert('Validation', 'Enter a valid payment amount.');
      return;
    }

    void runAction('Payment', async () => {
      const result = await payMutation.mutateAsync({
        orderId: order.id,
        payload: {
          amount: payAmount.trim(),
          method: payMethod,
          reference: payReference.trim() || null,
          note: payNote.trim() || null,
        },
      });
      setPayVisible(false);
      return result;
    });
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

        {organizationId !== null ? (
          <Pressable
            onPress={() => {
              void shareOrderPrintHtml(
                `/v1/purchase-orders/${order.id}/print`,
                `purchase-order-${order.id}.html`,
                organizationId,
              ).catch((error: Error) => {
                Alert.alert('Print failed', error.message);
              });
            }}
            style={styles.printButton}>
            <Text style={styles.printButtonText}>Share / print</Text>
          </Pressable>
        ) : null}

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
          {order.status === 'draft' && canUpdatePurchaseOrder(permissions) ? (
            <Link href={`/(app)/purchase-orders/${order.id}/edit`} style={styles.editLink}>
              Edit draft
            </Link>
          ) : null}

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
                label={receiveMutation.isPending ? 'Receiving…' : 'Receive stock'}
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
              label={payMutation.isPending ? 'Processing…' : 'Record payment'}
              onPress={handlePay}
            />
          ) : null}
        </View>
      </ScrollView>

      <LineItemsActionModal
        visible={receiveVisible}
        title="Receive stock"
        items={receiveItems}
        note={receiveNote}
        submitting={receiveMutation.isPending}
        submitLabel="Receive stock"
        onChangeQuantity={(id, quantity) => {
          setReceiveItems((current) =>
            current.map((item) => (item.id === id ? { ...item, quantity } : item)),
          );
        }}
        onChangeNote={setReceiveNote}
        onClose={() => setReceiveVisible(false)}
        onSubmit={submitReceive}
      />

      <PaymentActionModal
        visible={payVisible}
        title="Record payment"
        amount={payAmount}
        method={payMethod}
        reference={payReference}
        note={payNote}
        submitting={payMutation.isPending}
        onChangeAmount={setPayAmount}
        onChangeMethod={setPayMethod}
        onChangeReference={setPayReference}
        onChangeNote={setPayNote}
        onClose={() => setPayVisible(false)}
        onSubmit={submitPay}
      />
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
  printButton: {
    alignSelf: 'flex-start',
    backgroundColor: '#e2e8f0',
    borderRadius: 8,
    marginBottom: 16,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  printButtonText: {
    color: '#0f172a',
    fontSize: 14,
    fontWeight: '600',
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
  editLink: {
    color: '#2563eb',
    fontSize: 16,
    fontWeight: '700',
    marginBottom: 4,
    textAlign: 'center',
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
