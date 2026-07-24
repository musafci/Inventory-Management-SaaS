import { useEffect, useState } from 'react';
import { Alert, Pressable, StyleSheet, Text, View } from 'react-native';

import { EmptyWarehousesPrompt } from '@/components/EmptyWarehousesPrompt';
import { Button, ChipSelect, FormScreen, Input, LoadingState } from '@/components/ui';
import type { StockMovementPayload, StockMovementType } from '@/src/api/types';
import {
  useCachedProductsForPicker,
  useCreateStockMovement,
  useWarehouses,
} from '@/src/hooks/useInventory';
import { useNetwork } from '@/src/network/NetworkContext';
import { theme } from '@/src/theme';

type StockMovementFormProps = {
  onSuccess: () => void;
};

const movementTypes: { value: StockMovementType; label: string }[] = [
  { value: 'adjustment_in', label: 'Stock in' },
  { value: 'adjustment_out', label: 'Stock out' },
];

export function StockMovementForm({ onSuccess }: StockMovementFormProps) {
  const { isConnected } = useNetwork();
  const warehousesQuery = useWarehouses();
  const mutation = useCreateStockMovement();
  const [productSearch, setProductSearch] = useState('');
  const productsQuery = useCachedProductsForPicker(productSearch);
  const [warehouseId, setWarehouseId] = useState(0);
  const [productId, setProductId] = useState(0);
  const [type, setType] = useState<StockMovementType>('adjustment_in');
  const [quantity, setQuantity] = useState('1');
  const [note, setNote] = useState('');

  const warehouses = warehousesQuery.data ?? [];
  const products = productsQuery.data ?? [];

  useEffect(() => {
    if (warehouseId === 0 && warehouses[0]) {
      setWarehouseId(warehouses[0].id);
    }
  }, [warehouseId, warehouses]);

  useEffect(() => {
    if (productId === 0 && products[0]) {
      setProductId(products[0].id);
    }
  }, [productId, products]);

  if (warehousesQuery.isLoading) {
    return <LoadingState />;
  }

  if (warehouses.length === 0) {
    return (
      <EmptyWarehousesPrompt message="Add a warehouse before recording stock movements." />
    );
  }

  const handleSubmit = async () => {
    const parsedQuantity = Number.parseInt(quantity, 10);

    if (!Number.isFinite(parsedQuantity) || parsedQuantity < 1) {
      Alert.alert('Validation', 'Quantity must be at least 1.');

      return;
    }

    if (productId === 0) {
      Alert.alert('Validation', 'Select a product.');

      return;
    }

    const payload: StockMovementPayload = {
      warehouse_id: warehouseId,
      product_id: productId,
      type,
      quantity: parsedQuantity,
      note: note.trim() || null,
    };

    try {
      await mutation.mutateAsync(payload);

      if (!isConnected) {
        Alert.alert('Queued', 'Adjustment saved offline and will sync when you reconnect.');
      }

      onSuccess();
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Could not record movement.';
      Alert.alert('Failed', message);
    }
  };

  return (
    <FormScreen>
      {!isConnected ? (
        <View style={styles.offlineNote}>
          <Text style={styles.offlineText}>
            Offline mode: this adjustment will be queued for sync.
          </Text>
        </View>
      ) : null}

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
              onPress={() => setProductId(product.id)}
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

      <ChipSelect
        label="Movement type"
        options={movementTypes.map((option) => ({
          label: option.label,
          value: option.value,
        }))}
        value={type}
        onChange={setType}
      />

      <Input
        keyboardType="number-pad"
        label="Quantity"
        value={quantity}
        onChangeText={setQuantity}
      />

      <Input
        label="Note"
        multiline
        placeholder="Optional note"
        value={note}
        onChangeText={setNote}
      />

      <Button
        label="Record movement"
        loading={mutation.isPending}
        onPress={() => void handleSubmit()}
      />
    </FormScreen>
  );
}

const styles = StyleSheet.create({
  offlineNote: {
    backgroundColor: theme.colors.warningSoft,
    borderRadius: theme.radius.sm,
    marginBottom: theme.spacing.md,
    padding: theme.spacing.md,
  },
  offlineText: {
    color: theme.colors.warning,
    fontSize: 13,
  },
  fieldLabel: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    fontWeight: '600',
    marginBottom: theme.spacing.sm,
  },
  helper: {
    ...theme.typography.body,
    color: theme.colors.textSecondary,
    marginBottom: theme.spacing.md,
    textAlign: 'center',
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
