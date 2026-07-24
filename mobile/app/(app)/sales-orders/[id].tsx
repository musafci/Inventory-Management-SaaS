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
import { RefundActionModal } from '@/components/RefundActionModal';
import { ApiError } from '@/src/api/client';
import type { PaymentMethod, SalesOrder, SalesOrderItem } from '@/src/api/types';
import { useAuth } from '@/src/auth/AuthContext';
import { shareOrderPrintHtml } from '@/src/utils/shareOrderPrint';
import { useInventoryLabels } from '@/src/hooks/useInventory';
import {
  useCancelSalesOrder,
  useConfirmSalesOrder,
  useDeliverSalesOrder,
  useFulfillSalesOrder,
  usePaySalesOrder,
  useRefundSalesOrder,
  useSalesOrder,
} from '@/src/hooks/useOrders';
import { canCreatePayment, canUpdateSalesOrder } from '@/src/permissions';

function formatStatus(status: string): string {
  return status.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function parseAmount(value: string | undefined): number {
  const parsed = Number.parseFloat(value ?? '0');
  return Number.isFinite(parsed) ? parsed : 0;
}

function isSalesOrderPayable(order: SalesOrder): boolean {
  return ['confirmed', 'shipped', 'delivered'].includes(order.status) && parseAmount(order.amount_due) > 0;
}

function isSalesOrderRefundable(order: SalesOrder): boolean {
  return ['shipped', 'delivered'].includes(order.status) && parseAmount(order.amount_paid) > 0;
}

function productName(item: SalesOrderItem, labels: Map<number, string>): string {
  const nested = (item as SalesOrderItem & { product?: { name?: string } }).product?.name;
  return nested ?? labels.get(item.product_id) ?? `Product #${item.product_id}`;
}

export default function SalesOrderDetailScreen() {
  const { permissions, organizationId } = useAuth();
  const { id } = useLocalSearchParams<{ id: string }>();
  const orderId = Number(id);
  const query = useSalesOrder(Number.isFinite(orderId) ? orderId : null);
  const labelsQuery = useInventoryLabels();
  const confirmMutation = useConfirmSalesOrder();
  const cancelMutation = useCancelSalesOrder();
  const fulfillMutation = useFulfillSalesOrder();
  const deliverMutation = useDeliverSalesOrder();
  const payMutation = usePaySalesOrder();
  const refundMutation = useRefundSalesOrder();

  const [fulfillVisible, setFulfillVisible] = useState(false);
  const [fulfillItems, setFulfillItems] = useState<LineItemRow[]>([]);
  const [fulfillNote, setFulfillNote] = useState('');
  const [payVisible, setPayVisible] = useState(false);
  const [payAmount, setPayAmount] = useState('');
  const [payMethod, setPayMethod] = useState<PaymentMethod>('bank_transfer');
  const [payReference, setPayReference] = useState('');
  const [payNote, setPayNote] = useState('');
  const [refundVisible, setRefundVisible] = useState(false);
  const [refundAmount, setRefundAmount] = useState('');
  const [refundMethod, setRefundMethod] = useState<PaymentMethod>('bank_transfer');
  const [refundReference, setRefundReference] = useState('');
  const [refundNote, setRefundNote] = useState('');
  const [returnItems, setReturnItems] = useState<LineItemRow[]>([]);

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
        <Text style={styles.empty}>Sales order not found.</Text>
      </View>
    );
  }

  const order = query.data;
  const isPending =
    confirmMutation.isPending ||
    cancelMutation.isPending ||
    fulfillMutation.isPending ||
    deliverMutation.isPending ||
    payMutation.isPending ||
    refundMutation.isPending;

  const handleConfirm = () => {
    Alert.alert('Confirm order', 'Confirm this sales order?', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Confirm',
        onPress: () => {
          void runAction('Confirm', () => confirmMutation.mutateAsync(order.id));
        },
      },
    ]);
  };

  const handleCancel = () => {
    Alert.alert('Cancel order', 'Cancel this sales order?', [
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

  const handleFulfill = () => {
    const items = (order.items ?? [])
      .map((item) => ({
        id: item.id,
        label: productName(item, productLabels),
        maxQuantity: item.quantity - item.quantity_fulfilled,
        quantity: String(item.quantity - item.quantity_fulfilled),
      }))
      .filter((item) => item.maxQuantity > 0);

    if (items.length === 0) {
      Alert.alert('Fulfill', 'No remaining quantity to fulfill.');
      return;
    }

    setFulfillItems(items);
    setFulfillNote('');
    setFulfillVisible(true);
  };

  const submitFulfill = () => {
    const payloadItems = fulfillItems
      .map((item) => ({
        sales_order_item_id: item.id,
        quantity: Number.parseInt(item.quantity, 10),
      }))
      .filter((item) => Number.isFinite(item.quantity) && item.quantity > 0);

    if (payloadItems.length === 0) {
      Alert.alert('Validation', 'Enter at least one valid quantity.');
      return;
    }

    void runAction('Fulfill', async () => {
      const result = await fulfillMutation.mutateAsync({
        orderId: order.id,
        payload: {
          items: payloadItems,
          note: fulfillNote.trim() || null,
        },
      });
      setFulfillVisible(false);
      return result;
    });
  };

  const handleDeliver = () => {
    Alert.alert('Deliver order', 'Mark this order as delivered?', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Deliver',
        onPress: () => {
          void runAction('Deliver', () => deliverMutation.mutateAsync(order.id));
        },
      },
    ]);
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

  const handleRefund = () => {
    const items = (order.items ?? [])
      .map((item) => {
        const canReturn = item.quantity_fulfilled - item.quantity_returned;
        return {
          id: item.id,
          label: productName(item, productLabels),
          maxQuantity: canReturn,
          quantity: String(canReturn),
        };
      })
      .filter((item) => item.maxQuantity > 0);

    setRefundAmount(order.amount_paid ?? order.total_amount);
    setRefundMethod('bank_transfer');
    setRefundReference('');
    setRefundNote('');
    setReturnItems(items);
    setRefundVisible(true);
  };

  const submitRefund = () => {
    const parsedAmount = Number.parseFloat(refundAmount);
    if (!Number.isFinite(parsedAmount) || parsedAmount <= 0) {
      Alert.alert('Validation', 'Enter a valid refund amount.');
      return;
    }

    const payloadReturnItems = returnItems
      .map((item) => ({
        sales_order_item_id: item.id,
        quantity: Number.parseInt(item.quantity, 10),
      }))
      .filter((item) => Number.isFinite(item.quantity) && item.quantity > 0);

    void runAction('Refund', async () => {
      const result = await refundMutation.mutateAsync({
        orderId: order.id,
        payload: {
          amount: refundAmount.trim(),
          method: refundMethod,
          reference: refundReference.trim() || null,
          note: refundNote.trim() || null,
          return_items: payloadReturnItems.length > 0 ? payloadReturnItems : undefined,
        },
      });
      setRefundVisible(false);
      return result;
    });
  };

  return (
    <>
      <Stack.Screen options={{ title: order.order_number }} />

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
                `/v1/sales-orders/${order.id}/print`,
                `sales-order-${order.id}.html`,
                organizationId,
              ).catch((error: Error) => {
                Alert.alert('Print failed', error.message);
              });
            }}
            style={styles.printButton}>
            <Text style={styles.printButtonText}>Share / print</Text>
          </Pressable>
        ) : null}

        <DetailRow label="Customer" value={order.customer?.name ?? `#${order.customer_id}`} />
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
                  Qty {item.quantity} · Fulfilled {item.quantity_fulfilled} · Returned{' '}
                  {item.quantity_returned}
                </Text>
                <Text style={styles.itemMeta}>
                  Unit price {item.unit_price} · Subtotal {item.subtotal}
                </Text>
              </View>
            ))}
          </View>
        ) : null}

        <View style={styles.actions}>
          {order.status === 'draft' && canUpdateSalesOrder(permissions) ? (
            <Link href={`/(app)/sales-orders/${order.id}/edit`} style={styles.editLink}>
              Edit draft
            </Link>
          ) : null}

          {order.status === 'draft' ? (
            <>
              <ActionButton
                disabled={isPending}
                label={confirmMutation.isPending ? 'Confirming…' : 'Confirm order'}
                onPress={handleConfirm}
              />
              <ActionButton
                disabled={isPending}
                label={cancelMutation.isPending ? 'Cancelling…' : 'Cancel order'}
                onPress={handleCancel}
                variant="danger"
              />
            </>
          ) : null}

          {order.status === 'confirmed' ? (
            <>
              <ActionButton
                disabled={isPending}
                label={fulfillMutation.isPending ? 'Fulfilling…' : 'Fulfill order'}
                onPress={handleFulfill}
              />
              <ActionButton
                disabled={isPending}
                label={cancelMutation.isPending ? 'Cancelling…' : 'Cancel order'}
                onPress={handleCancel}
                variant="danger"
              />
            </>
          ) : null}

          {order.status === 'shipped' ? (
            <ActionButton
              disabled={isPending}
              label={deliverMutation.isPending ? 'Delivering…' : 'Mark delivered'}
              onPress={handleDeliver}
            />
          ) : null}

          {isSalesOrderPayable(order) && canCreatePayment(permissions) ? (
            <ActionButton
              disabled={isPending}
              label={payMutation.isPending ? 'Processing…' : 'Record payment'}
              onPress={handlePay}
            />
          ) : null}

          {isSalesOrderRefundable(order) && canCreatePayment(permissions) ? (
            <ActionButton
              disabled={isPending}
              label={refundMutation.isPending ? 'Processing…' : 'Process refund'}
              onPress={handleRefund}
              variant="danger"
            />
          ) : null}
        </View>
      </ScrollView>

      <LineItemsActionModal
        visible={fulfillVisible}
        title="Fulfill order"
        items={fulfillItems}
        note={fulfillNote}
        submitting={fulfillMutation.isPending}
        submitLabel="Fulfill order"
        onChangeQuantity={(id, quantity) => {
          setFulfillItems((current) =>
            current.map((item) => (item.id === id ? { ...item, quantity } : item)),
          );
        }}
        onChangeNote={setFulfillNote}
        onClose={() => setFulfillVisible(false)}
        onSubmit={submitFulfill}
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

      <RefundActionModal
        visible={refundVisible}
        title="Process refund"
        amount={refundAmount}
        method={refundMethod}
        reference={refundReference}
        note={refundNote}
        returnItems={returnItems}
        submitting={refundMutation.isPending}
        onChangeAmount={setRefundAmount}
        onChangeMethod={setRefundMethod}
        onChangeReference={setRefundReference}
        onChangeNote={setRefundNote}
        onChangeReturnQuantity={(id, quantity) => {
          setReturnItems((current) =>
            current.map((item) => (item.id === id ? { ...item, quantity } : item)),
          );
        }}
        onClose={() => setRefundVisible(false)}
        onSubmit={submitRefund}
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
