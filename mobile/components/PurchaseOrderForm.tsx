import { useEffect, useState } from 'react';
import { Alert, Pressable, StyleSheet, Text, View } from 'react-native';

import { EmptyWarehousesPrompt } from '@/components/EmptyWarehousesPrompt';
import { Button, ChipSelect, FormScreen, Input, LoadingState } from '@/components/ui';
import { ApiError } from '@/src/api/client';
import type { PurchaseOrder, PurchaseOrderPayload } from '@/src/api/types';
import { useCachedProductsForPicker, useWarehouses } from '@/src/hooks/useInventory';
import { useCreatePurchaseOrder, useUpdatePurchaseOrder } from '@/src/hooks/useOrders';
import { useSuppliersList } from '@/src/hooks/usePartners';
import { theme } from '@/src/theme';

type PurchaseOrderFormProps = {
  order?: PurchaseOrder;
  onSuccess: (orderId: number) => void;
};

function todayIsoDate(): string {
  return new Date().toISOString().slice(0, 10);
}

export function PurchaseOrderForm({ order, onSuccess }: PurchaseOrderFormProps) {
  const isEditing = order !== undefined;
  const createMutation = useCreatePurchaseOrder();
  const updateMutation = useUpdatePurchaseOrder(order?.id ?? 0);
  const mutation = isEditing ? updateMutation : createMutation;
  const warehousesQuery = useWarehouses();
  const suppliers = useSuppliersList('');
  const [productSearch, setProductSearch] = useState('');
  const productsQuery = useCachedProductsForPicker(productSearch);
  const firstItem = order?.items?.[0];
  const [supplierId, setSupplierId] = useState(order?.supplier_id ?? 0);
  const [warehouseId, setWarehouseId] = useState(order?.warehouse_id ?? 0);
  const [productId, setProductId] = useState(firstItem?.product_id ?? 0);
  const [quantity, setQuantity] = useState(String(firstItem?.quantity_ordered ?? 1));
  const [unitCost, setUnitCost] = useState(firstItem?.unit_cost ?? '');
  const [orderDate, setOrderDate] = useState(order?.order_date ?? todayIsoDate());

  const warehouses = warehousesQuery.data ?? [];
  const products = productsQuery.data ?? [];

  useEffect(() => {
    if (isEditing) {
      return;
    }

    if (supplierId === 0 && suppliers[0]) {
      setSupplierId(suppliers[0].id);
    }
  }, [isEditing, supplierId, suppliers]);

  useEffect(() => {
    if (isEditing) {
      return;
    }

    if (warehouseId === 0 && warehouses[0]) {
      setWarehouseId(warehouses[0].id);
    }
  }, [isEditing, warehouseId, warehouses]);

  useEffect(() => {
    if (isEditing) {
      return;
    }

    if (productId === 0 && products[0]) {
      setProductId(products[0].id);
      if (!unitCost) {
        setUnitCost(products[0].cost_price);
      }
    }
  }, [isEditing, productId, products, unitCost]);

  if (warehousesQuery.isLoading) {
    return <LoadingState />;
  }

  if (warehouses.length === 0) {
    return (
      <EmptyWarehousesPrompt message="Add a warehouse before creating purchase orders." />
    );
  }

  if (suppliers.length === 0) {
    return (
      <View style={styles.emptyWrap}>
        <Text style={styles.helper}>Add a supplier before creating purchase orders.</Text>
      </View>
    );
  }

  const handleSubmit = async () => {
    const parsedQuantity = Number.parseInt(quantity, 10);
    const parsedCost = Number.parseFloat(unitCost);

    if (supplierId === 0) {
      Alert.alert('Validation', 'Select a supplier.');
      return;
    }

    if (productId === 0) {
      Alert.alert('Validation', 'Select a product.');
      return;
    }

    if (!Number.isFinite(parsedQuantity) || parsedQuantity < 1) {
      Alert.alert('Validation', 'Quantity must be at least 1.');
      return;
    }

    if (!Number.isFinite(parsedCost) || parsedCost < 0) {
      Alert.alert('Validation', 'Enter a valid unit cost.');
      return;
    }

    if (!/^\d{4}-\d{2}-\d{2}$/.test(orderDate.trim())) {
      Alert.alert('Validation', 'Order date must be YYYY-MM-DD.');
      return;
    }

    const payload: PurchaseOrderPayload = {
      supplier_id: supplierId,
      warehouse_id: warehouseId,
      order_date: orderDate.trim(),
      items: [
        {
          product_id: productId,
          quantity_ordered: parsedQuantity,
          unit_cost: parsedCost,
        },
      ],
    };

    try {
      const orderResult = await mutation.mutateAsync(payload);

      if (orderResult) {
        onSuccess(orderResult.id);
      } else if (!isEditing) {
        Alert.alert(
          'Queued offline',
          'Purchase order will be created when you reconnect.',
        );
        onSuccess(0);
      } else {
        onSuccess(order?.id ?? 0);
      }
    } catch (error) {
      const message = error instanceof ApiError
        ? error.message
        : `Could not ${isEditing ? 'update' : 'create'} purchase order.`;
      Alert.alert(isEditing ? 'Update failed' : 'Create failed', message);
    }
  };

  return (
    <FormScreen>
      <ChipSelect
        label="Supplier"
        options={suppliers.slice(0, 20).map((supplier) => ({
          label: supplier.name,
          value: String(supplier.id),
        }))}
        value={String(supplierId)}
        onChange={(value) => setSupplierId(Number(value))}
      />

      <ChipSelect
        label="Warehouse"
        options={warehouses.map((warehouse) => ({
          label: warehouse.name,
          value: String(warehouse.id),
        }))}
        value={String(warehouseId)}
        onChange={(value) => setWarehouseId(Number(value))}
      />

      <Input
        label="Product search"
        placeholder="Search cached products"
        value={productSearch}
        onChangeText={(value) => {
          setProductSearch(value);
          setProductId(0);
        }}
      />

      <Text style={styles.fieldLabel}>Product</Text>
      {products.length === 0 ? (
        <Text style={styles.helper}>No cached products match. Sync or search again.</Text>
      ) : (
        <View style={styles.chipRow}>
          {products.slice(0, 12).map((product) => (
            <Pressable
              key={product.id}
              onPress={() => {
                setProductId(product.id);
                setUnitCost(product.cost_price);
              }}
              style={[styles.chip, productId === product.id ? styles.chipSelected : null]}>
              <Text
                style={[
                  styles.chipText,
                  productId === product.id ? styles.chipTextSelected : null,
                ]}>
                {product.sku ? `${product.name} (${product.sku})` : product.name}
              </Text>
            </Pressable>
          ))}
        </View>
      )}

      <Input
        keyboardType="number-pad"
        label="Quantity"
        value={quantity}
        onChangeText={setQuantity}
      />

      <Input
        keyboardType="decimal-pad"
        label="Unit cost"
        value={unitCost}
        onChangeText={setUnitCost}
      />

      <Input
        autoCapitalize="none"
        label="Order date"
        placeholder="YYYY-MM-DD"
        value={orderDate}
        onChangeText={setOrderDate}
      />

      <Button
        label={isEditing ? 'Save changes' : 'Create purchase order'}
        loading={mutation.isPending}
        onPress={() => void handleSubmit()}
      />
    </FormScreen>
  );
}

const styles = StyleSheet.create({
  emptyWrap: {
    alignItems: 'center',
    flex: 1,
    justifyContent: 'center',
    padding: theme.spacing.xxxl,
  },
  helper: {
    ...theme.typography.body,
    color: theme.colors.textSecondary,
    textAlign: 'center',
  },
  fieldLabel: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    fontWeight: '600',
    marginBottom: theme.spacing.sm,
  },
  chipRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: theme.spacing.sm,
    marginBottom: theme.spacing.md,
  },
  chip: {
    backgroundColor: theme.colors.surfaceMuted,
    borderColor: theme.colors.border,
    borderRadius: theme.radius.pill,
    borderWidth: 1,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  chipSelected: {
    backgroundColor: theme.colors.primary,
    borderColor: theme.colors.primary,
  },
  chipText: {
    color: theme.colors.textSecondary,
    fontSize: 13,
    fontWeight: '700',
  },
  chipTextSelected: {
    color: theme.colors.primaryText,
  },
});
