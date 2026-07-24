import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { useMemo, useState } from 'react';
import { Alert, StyleSheet, Text, View } from 'react-native';

import { LineItemsActionModal, type LineItemRow } from '@/components/LineItemsActionModal';
import { PaymentActionModal } from '@/components/PaymentActionModal';
import {
  Button,
  Card,
  DetailRow,
  EmptyState,
  LoadingState,
  ScreenContainer,
  ScreenScrollView,
  SectionHeader,
  StatusBadge,
} from '@/components/ui';
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
import { theme } from '@/src/theme';

function formatStatus(status: string): string {
  return status.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function orderStatusTone(status: string): 'default' | 'success' | 'warning' | 'danger' | 'info' {
  if (status.includes('cancel')) return 'danger';
  if (status.includes('received')) return 'success';
  if (status.includes('draft')) return 'default';
  if (status.includes('partial')) return 'warning';
  return 'info';
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
  const router = useRouter();
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
      <ScreenContainer>
        <LoadingState />
      </ScreenContainer>
    );
  }

  if (!query.data) {
    return (
      <ScreenContainer>
        <EmptyState title="Purchase order not found." />
      </ScreenContainer>
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

      <ScreenScrollView
        refreshing={query.isRefetching}
        onRefresh={() => {
          void query.refetch();
        }}>
        <View style={styles.statusRow}>
          <StatusBadge label={formatStatus(order.status)} tone={orderStatusTone(order.status)} />
          <Text style={styles.metaText}>Order date: {order.order_date}</Text>
        </View>

        {organizationId !== null ? (
          <Button
            label="Share / print"
            variant="secondary"
            onPress={() => {
              void shareOrderPrintHtml(
                `/v1/purchase-orders/${order.id}/print`,
                `purchase-order-${order.id}.html`,
                organizationId,
              ).catch((error: Error) => {
                Alert.alert('Print failed', error.message);
              });
            }}
            style={styles.printButton}
          />
        ) : null}

        <Card>
          <DetailRow label="Supplier" value={order.supplier?.name ?? `#${order.supplier_id}`} />
          <DetailRow label="Warehouse" value={warehouseLabels.get(order.warehouse_id) ?? `#${order.warehouse_id}`} />
          <DetailRow label="Total amount" value={order.total_amount} />
          <DetailRow label="Amount paid" value={order.amount_paid ?? '0.00'} />
          <DetailRow label="Amount due" value={order.amount_due ?? '0.00'} />
        </Card>

        {order.items && order.items.length > 0 ? (
          <View style={styles.section}>
            <SectionHeader title="Line items" />
            {order.items.map((item) => (
              <Card key={item.id} style={styles.itemCard}>
                <Text style={styles.itemName}>{productName(item, productLabels)}</Text>
                <Text style={styles.itemMeta}>
                  Ordered {item.quantity_ordered} · Received {item.quantity_received} · Remaining{' '}
                  {item.quantity_remaining}
                </Text>
                <Text style={styles.itemMeta}>
                  Unit cost {item.unit_cost} · Subtotal {item.subtotal}
                </Text>
              </Card>
            ))}
          </View>
        ) : null}

        <View style={styles.actions}>
          {order.status === 'draft' && canUpdatePurchaseOrder(permissions) ? (
            <Button
              label="Edit draft"
              variant="secondary"
              onPress={() => router.push(`/(app)/purchase-orders/${order.id}/edit`)}
            />
          ) : null}

          {order.status === 'draft' ? (
            <>
              <Button
                disabled={isPending}
                label={sendMutation.isPending ? 'Sending…' : 'Send order'}
                loading={sendMutation.isPending}
                onPress={handleSend}
              />
              <Button
                disabled={isPending}
                label={cancelMutation.isPending ? 'Cancelling…' : 'Cancel order'}
                loading={cancelMutation.isPending}
                variant="danger"
                onPress={handleCancel}
              />
            </>
          ) : null}

          {['sent', 'partially_received'].includes(order.status) ? (
            <>
              <Button
                disabled={isPending}
                label={receiveMutation.isPending ? 'Receiving…' : 'Receive stock'}
                loading={receiveMutation.isPending}
                onPress={handleReceive}
              />
              {order.status === 'sent' ? (
                <Button
                  disabled={isPending}
                  label={cancelMutation.isPending ? 'Cancelling…' : 'Cancel order'}
                  loading={cancelMutation.isPending}
                  variant="danger"
                  onPress={handleCancel}
                />
              ) : null}
            </>
          ) : null}

          {isPurchaseOrderPayable(order) && canCreatePayment(permissions) ? (
            <Button
              disabled={isPending}
              label={payMutation.isPending ? 'Processing…' : 'Record payment'}
              loading={payMutation.isPending}
              onPress={handlePay}
            />
          ) : null}
        </View>
      </ScreenScrollView>

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

const styles = StyleSheet.create({
  statusRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: theme.spacing.sm,
    marginBottom: theme.spacing.lg,
  },
  printButton: {
    alignSelf: 'flex-start',
    marginBottom: theme.spacing.lg,
    minHeight: 44,
  },
  metaText: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
  },
  section: {
    marginTop: theme.spacing.sm,
  },
  itemCard: {
    marginBottom: theme.spacing.sm,
    padding: theme.spacing.md,
  },
  itemName: {
    ...theme.typography.bodyStrong,
    color: theme.colors.text,
  },
  itemMeta: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    marginTop: 4,
  },
  actions: {
    gap: theme.spacing.sm,
    marginTop: theme.spacing.xl,
  },
});
