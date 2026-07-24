import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { useMemo, useState } from 'react';
import { Alert, StyleSheet, Text, View } from 'react-native';

import { LineItemsActionModal, type LineItemRow } from '@/components/LineItemsActionModal';
import { PaymentActionModal } from '@/components/PaymentActionModal';
import { RefundActionModal } from '@/components/RefundActionModal';
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
import { theme } from '@/src/theme';

function formatStatus(status: string): string {
  return status.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function orderStatusTone(status: string): 'default' | 'success' | 'warning' | 'danger' | 'info' {
  if (status.includes('cancel')) return 'danger';
  if (status.includes('deliver')) return 'success';
  if (status.includes('draft')) return 'default';
  if (status.includes('partial')) return 'warning';
  return 'info';
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
  const router = useRouter();
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
      <ScreenContainer>
        <LoadingState />
      </ScreenContainer>
    );
  }

  if (!query.data) {
    return (
      <ScreenContainer>
        <EmptyState title="Sales order not found." />
      </ScreenContainer>
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
                `/v1/sales-orders/${order.id}/print`,
                `sales-order-${order.id}.html`,
                organizationId,
              ).catch((error: Error) => {
                Alert.alert('Print failed', error.message);
              });
            }}
            style={styles.printButton}
          />
        ) : null}

        <Card>
          <DetailRow label="Customer" value={order.customer?.name ?? `#${order.customer_id}`} />
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
                  Qty {item.quantity} · Fulfilled {item.quantity_fulfilled} · Returned{' '}
                  {item.quantity_returned}
                </Text>
                <Text style={styles.itemMeta}>
                  Unit price {item.unit_price} · Subtotal {item.subtotal}
                </Text>
              </Card>
            ))}
          </View>
        ) : null}

        <View style={styles.actions}>
          {order.status === 'draft' && canUpdateSalesOrder(permissions) ? (
            <Button
              label="Edit draft"
              variant="secondary"
              onPress={() => router.push(`/(app)/sales-orders/${order.id}/edit`)}
            />
          ) : null}

          {order.status === 'draft' ? (
            <>
              <Button
                disabled={isPending}
                label={confirmMutation.isPending ? 'Confirming…' : 'Confirm order'}
                loading={confirmMutation.isPending}
                onPress={handleConfirm}
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

          {order.status === 'confirmed' ? (
            <>
              <Button
                disabled={isPending}
                label={fulfillMutation.isPending ? 'Fulfilling…' : 'Fulfill order'}
                loading={fulfillMutation.isPending}
                onPress={handleFulfill}
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

          {order.status === 'shipped' ? (
            <Button
              disabled={isPending}
              label={deliverMutation.isPending ? 'Delivering…' : 'Mark delivered'}
              loading={deliverMutation.isPending}
              onPress={handleDeliver}
            />
          ) : null}

          {isSalesOrderPayable(order) && canCreatePayment(permissions) ? (
            <Button
              disabled={isPending}
              label={payMutation.isPending ? 'Processing…' : 'Record payment'}
              loading={payMutation.isPending}
              onPress={handlePay}
            />
          ) : null}

          {isSalesOrderRefundable(order) && canCreatePayment(permissions) ? (
            <Button
              disabled={isPending}
              label={refundMutation.isPending ? 'Processing…' : 'Process refund'}
              loading={refundMutation.isPending}
              variant="danger"
              onPress={handleRefund}
            />
          ) : null}
        </View>
      </ScreenScrollView>

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
