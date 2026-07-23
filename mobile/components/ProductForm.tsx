import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  TextInput,
  View,
} from 'react-native';

import type { ProductPayload } from '@/src/api/types';
import { useCategories, useUnits } from '@/src/hooks/useProducts';

type ProductFormProps = {
  initialValues?: Partial<ProductPayload>;
  submitLabel: string;
  isSubmitting: boolean;
  onSubmit: (payload: ProductPayload) => Promise<void>;
};

const emptyForm: ProductPayload = {
  category_id: 0,
  unit_id: 0,
  name: '',
  sku: '',
  barcode: '',
  cost_price: '0',
  selling_price: '0',
  tax_rate: '0',
  reorder_point: null,
  is_active: true,
};

export function ProductForm({
  initialValues,
  submitLabel,
  isSubmitting,
  onSubmit,
}: ProductFormProps) {
  const categoriesQuery = useCategories();
  const unitsQuery = useUnits();
  const [form, setForm] = useState<ProductPayload>({ ...emptyForm, ...initialValues });

  useEffect(() => {
    if (initialValues) {
      setForm({ ...emptyForm, ...initialValues });
    }
  }, [initialValues]);

  useEffect(() => {
    if (form.category_id === 0 && categoriesQuery.data?.[0]) {
      setForm((current) => ({ ...current, category_id: categoriesQuery.data![0].id }));
    }

    if (form.unit_id === 0 && unitsQuery.data?.[0]) {
      setForm((current) => ({ ...current, unit_id: unitsQuery.data![0].id }));
    }
  }, [categoriesQuery.data, form.category_id, form.unit_id, unitsQuery.data]);

  if (categoriesQuery.isLoading || unitsQuery.isLoading) {
    return (
      <View style={styles.loading}>
        <ActivityIndicator size="large" />
      </View>
    );
  }

  const categories = categoriesQuery.data ?? [];
  const units = unitsQuery.data ?? [];

  if (categories.length === 0 || units.length === 0) {
    return (
      <View style={styles.loading}>
        <Text style={styles.helper}>
          Add at least one category and unit on the web app before creating products.
        </Text>
      </View>
    );
  }

  const handleSubmit = async () => {
    if (!form.name.trim()) {
      Alert.alert('Validation', 'Product name is required.');

      return;
    }

    await onSubmit({
      ...form,
      name: form.name.trim(),
      sku: form.sku?.trim() || null,
      barcode: form.barcode?.trim() || null,
    });
  };

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <Text style={styles.label}>Name</Text>
      <TextInput
        value={form.name}
        onChangeText={(name) => setForm((current) => ({ ...current, name }))}
        style={styles.input}
        placeholder="Product name"
      />

      <Text style={styles.label}>SKU</Text>
      <TextInput
        value={form.sku ?? ''}
        onChangeText={(sku) => setForm((current) => ({ ...current, sku }))}
        style={styles.input}
        placeholder="SKU"
        autoCapitalize="characters"
      />

      <Text style={styles.label}>Barcode</Text>
      <TextInput
        value={form.barcode ?? ''}
        onChangeText={(barcode) => setForm((current) => ({ ...current, barcode }))}
        style={styles.input}
        placeholder="Barcode"
        keyboardType="number-pad"
      />

      <Text style={styles.label}>Category</Text>
      <View style={styles.chipRow}>
        {categories.map((category) => (
          <Pressable
            key={category.id}
            onPress={() => setForm((current) => ({ ...current, category_id: category.id }))}
            style={[
              styles.chip,
              form.category_id === category.id ? styles.chipSelected : null,
            ]}>
            <Text
              style={[
                styles.chipText,
                form.category_id === category.id ? styles.chipTextSelected : null,
              ]}>
              {category.name}
            </Text>
          </Pressable>
        ))}
      </View>

      <Text style={styles.label}>Unit</Text>
      <View style={styles.chipRow}>
        {units.map((unit) => (
          <Pressable
            key={unit.id}
            onPress={() => setForm((current) => ({ ...current, unit_id: unit.id }))}
            style={[styles.chip, form.unit_id === unit.id ? styles.chipSelected : null]}>
            <Text
              style={[
                styles.chipText,
                form.unit_id === unit.id ? styles.chipTextSelected : null,
              ]}>
              {unit.symbol ? `${unit.name} (${unit.symbol})` : unit.name}
            </Text>
          </Pressable>
        ))}
      </View>

      <Text style={styles.label}>Cost price</Text>
      <TextInput
        value={String(form.cost_price ?? '')}
        onChangeText={(cost_price) => setForm((current) => ({ ...current, cost_price }))}
        style={styles.input}
        keyboardType="decimal-pad"
      />

      <Text style={styles.label}>Selling price</Text>
      <TextInput
        value={String(form.selling_price ?? '')}
        onChangeText={(selling_price) => setForm((current) => ({ ...current, selling_price }))}
        style={styles.input}
        keyboardType="decimal-pad"
      />

      <Text style={styles.label}>Tax rate (%)</Text>
      <TextInput
        value={String(form.tax_rate ?? '')}
        onChangeText={(tax_rate) => setForm((current) => ({ ...current, tax_rate }))}
        style={styles.input}
        keyboardType="decimal-pad"
      />

      <Text style={styles.label}>Reorder point</Text>
      <TextInput
        value={form.reorder_point === null ? '' : String(form.reorder_point)}
        onChangeText={(value) => setForm((current) => ({
          ...current,
          reorder_point: value.trim() === '' ? null : Number(value),
        }))}
        style={styles.input}
        keyboardType="number-pad"
      />

      <View style={styles.switchRow}>
        <Text style={styles.labelInline}>Active</Text>
        <Switch
          value={form.is_active ?? true}
          onValueChange={(is_active) => setForm((current) => ({ ...current, is_active }))}
        />
      </View>

      <Pressable
        disabled={isSubmitting}
        onPress={() => {
          void handleSubmit();
        }}
        style={[styles.button, isSubmitting ? styles.buttonDisabled : null]}>
        <Text style={styles.buttonText}>{isSubmitting ? 'Saving…' : submitLabel}</Text>
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
  labelInline: {
    color: '#334155',
    fontSize: 14,
    fontWeight: '600',
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
  switchRow: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 16,
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
