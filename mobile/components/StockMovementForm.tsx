import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';

import { EmptyWarehousesPrompt } from '@/components/EmptyWarehousesPrompt';
import type { StockMovementPayload, StockMovementType } from '@/src/api/types';
import {
  useCachedProductsForPicker,
  useCreateStockMovement,
  useWarehouses,
} from '@/src/hooks/useInventory';
import { useNetwork } from '@/src/network/NetworkContext';

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
    return (
      <View style={styles.loading}>
        <ActivityIndicator size="large" />
      </View>
    );
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
    <ScrollView contentContainerStyle={styles.container}>
      {!isConnected ? (
        <Text style={styles.offlineNote}>
          Offline mode: this adjustment will be queued for sync.
        </Text>
      ) : null}

      <Text style={styles.label}>Warehouse</Text>
      <View style={styles.chipRow}>
        {warehouses.map((warehouse) => (
          <Pressable
            key={warehouse.id}
            onPress={() => setWarehouseId(warehouse.id)}
            style={[styles.chip, warehouseId === warehouse.id ? styles.chipSelected : null]}>
            <Text
              style={[
                styles.chipText,
                warehouseId === warehouse.id ? styles.chipTextSelected : null,
              ]}>
              {warehouse.name}
            </Text>
          </Pressable>
        ))}
      </View>

      <Text style={styles.label}>Product search</Text>
      <TextInput
        value={productSearch}
        onChangeText={(value) => {
          setProductSearch(value);
          setProductId(0);
        }}
        placeholder="Search cached products"
        style={styles.input}
      />

      <Text style={styles.label}>Product</Text>
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

      <Text style={styles.label}>Movement type</Text>
      <View style={styles.chipRow}>
        {movementTypes.map((option) => (
          <Pressable
            key={option.value}
            onPress={() => setType(option.value)}
            style={[styles.chip, type === option.value ? styles.chipSelected : null]}>
            <Text
              style={[
                styles.chipText,
                type === option.value ? styles.chipTextSelected : null,
              ]}>
              {option.label}
            </Text>
          </Pressable>
        ))}
      </View>

      <Text style={styles.label}>Quantity</Text>
      <TextInput
        value={quantity}
        onChangeText={setQuantity}
        keyboardType="number-pad"
        style={styles.input}
      />

      <Text style={styles.label}>Note</Text>
      <TextInput
        value={note}
        onChangeText={setNote}
        placeholder="Optional note"
        style={[styles.input, styles.noteInput]}
        multiline
      />

      <Pressable
        disabled={mutation.isPending}
        onPress={() => {
          void handleSubmit();
        }}
        style={[styles.button, mutation.isPending ? styles.buttonDisabled : null]}>
        <Text style={styles.buttonText}>{mutation.isPending ? 'Saving…' : 'Record movement'}</Text>
      </Pressable>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    padding: 16,
    paddingBottom: 40,
  },
  loading: {
    alignItems: 'center',
    flex: 1,
    justifyContent: 'center',
    padding: 24,
  },
  helper: {
    color: '#64748b',
    fontSize: 15,
    lineHeight: 22,
    textAlign: 'center',
  },
  offlineNote: {
    backgroundColor: '#fef3c7',
    borderRadius: 8,
    color: '#92400e',
    fontSize: 13,
    marginBottom: 12,
    padding: 10,
  },
  label: {
    color: '#334155',
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 8,
    marginTop: 12,
  },
  input: {
    backgroundColor: '#fff',
    borderColor: '#cbd5e1',
    borderRadius: 10,
    borderWidth: 1,
    fontSize: 16,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  noteInput: {
    minHeight: 80,
    textAlignVertical: 'top',
  },
  chipRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  chip: {
    backgroundColor: '#e2e8f0',
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  chipSelected: {
    backgroundColor: '#2563eb',
  },
  chipText: {
    color: '#334155',
    fontSize: 13,
    fontWeight: '600',
  },
  chipTextSelected: {
    color: '#fff',
  },
  button: {
    alignItems: 'center',
    backgroundColor: '#2563eb',
    borderRadius: 10,
    marginTop: 24,
    paddingVertical: 14,
  },
  buttonDisabled: {
    opacity: 0.6,
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '700',
  },
});
