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

import { ApiError } from '@/src/api/client';
import type { PurchaseOrderPayload } from '@/src/api/types';
import { useCachedProductsForPicker, useWarehouses } from '@/src/hooks/useInventory';
import { useCreatePurchaseOrder } from '@/src/hooks/useOrders';
import { useSuppliersList } from '@/src/hooks/usePartners';

type PurchaseOrderFormProps = {
  onSuccess: (orderId: number) => void;
};

function todayIsoDate(): string {
  return new Date().toISOString().slice(0, 10);
}

export function PurchaseOrderForm({ onSuccess }: PurchaseOrderFormProps) {
  const mutation = useCreatePurchaseOrder();
  const warehousesQuery = useWarehouses();
  const suppliers = useSuppliersList('');
  const [productSearch, setProductSearch] = useState('');
  const productsQuery = useCachedProductsForPicker(productSearch);
  const [supplierId, setSupplierId] = useState(0);
  const [warehouseId, setWarehouseId] = useState(0);
  const [productId, setProductId] = useState(0);
  const [quantity, setQuantity] = useState('1');
  const [unitCost, setUnitCost] = useState('');
  const [orderDate, setOrderDate] = useState(todayIsoDate());

  const warehouses = warehousesQuery.data ?? [];
  const products = productsQuery.data ?? [];

  useEffect(() => {
    if (supplierId === 0 && suppliers[0]) {
      setSupplierId(suppliers[0].id);
    }
  }, [supplierId, suppliers]);

  useEffect(() => {
    if (warehouseId === 0 && warehouses[0]) {
      setWarehouseId(warehouses[0].id);
    }
  }, [warehouseId, warehouses]);

  useEffect(() => {
    if (productId === 0 && products[0]) {
      setProductId(products[0].id);
      if (!unitCost) {
        setUnitCost(products[0].cost_price);
      }
    }
  }, [productId, products, unitCost]);

  if (warehousesQuery.isLoading) {
    return (
      <View style={styles.loading}>
        <ActivityIndicator size="large" />
      </View>
    );
  }

  if (warehouses.length === 0) {
    return (
      <View style={styles.loading}>
        <Text style={styles.helper}>Create a warehouse on the web app before creating purchase orders.</Text>
      </View>
    );
  }

  if (suppliers.length === 0) {
    return (
      <View style={styles.loading}>
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
      const order = await mutation.mutateAsync(payload);

      if (order) {
        onSuccess(order.id);
      } else {
        Alert.alert(
          'Queued offline',
          'Purchase order will be created when you reconnect.',
        );
        onSuccess(0);
      }
    } catch (error) {
      const message = error instanceof ApiError ? error.message : 'Could not create purchase order.';
      Alert.alert('Create failed', message);
    }
  };

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <Text style={styles.label}>Supplier</Text>
      <View style={styles.chipRow}>
        {suppliers.slice(0, 20).map((supplier) => (
          <Pressable
            key={supplier.id}
            onPress={() => setSupplierId(supplier.id)}
            style={[styles.chip, supplierId === supplier.id ? styles.chipSelected : null]}>
            <Text
              style={[
                styles.chipText,
                supplierId === supplier.id ? styles.chipTextSelected : null,
              ]}>
              {supplier.name}
            </Text>
          </Pressable>
        ))}
      </View>

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

      <Text style={styles.label}>Quantity</Text>
      <TextInput
        value={quantity}
        onChangeText={setQuantity}
        keyboardType="number-pad"
        style={styles.input}
      />

      <Text style={styles.label}>Unit cost</Text>
      <TextInput
        value={unitCost}
        onChangeText={setUnitCost}
        keyboardType="decimal-pad"
        style={styles.input}
      />

      <Text style={styles.label}>Order date</Text>
      <TextInput
        value={orderDate}
        onChangeText={setOrderDate}
        placeholder="YYYY-MM-DD"
        autoCapitalize="none"
        style={styles.input}
      />

      <Pressable
        disabled={mutation.isPending}
        onPress={() => {
          void handleSubmit();
        }}
        style={[styles.button, mutation.isPending ? styles.buttonDisabled : null]}>
        <Text style={styles.buttonText}>{mutation.isPending ? 'Saving…' : 'Create purchase order'}</Text>
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
