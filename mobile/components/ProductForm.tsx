import { useEffect, useState } from 'react';
import { Alert, StyleSheet, Switch, Text, View } from 'react-native';

import { EmptyCatalogPrompt } from '@/components/EmptyCatalogPrompt';
import { Button, ChipSelect, FormScreen, Input, LoadingState } from '@/components/ui';
import type { ProductPayload } from '@/src/api/types';
import { useCategories, useUnits } from '@/src/hooks/useProducts';
import { theme } from '@/src/theme';

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
    return <LoadingState />;
  }

  const categories = categoriesQuery.data ?? [];
  const units = unitsQuery.data ?? [];

  if (categories.length === 0 || units.length === 0) {
    return (
      <EmptyCatalogPrompt
        missingCategories={categories.length === 0}
        missingUnits={units.length === 0}
      />
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
    <FormScreen>
      <Input
        label="Name"
        placeholder="Product name"
        value={form.name}
        onChangeText={(name) => setForm((current) => ({ ...current, name }))}
      />

      <Input
        autoCapitalize="characters"
        label="SKU"
        placeholder="SKU"
        value={form.sku ?? ''}
        onChangeText={(sku) => setForm((current) => ({ ...current, sku }))}
      />

      <Input
        keyboardType="number-pad"
        label="Barcode"
        placeholder="Barcode"
        value={form.barcode ?? ''}
        onChangeText={(barcode) => setForm((current) => ({ ...current, barcode }))}
      />

      <ChipSelect
        label="Category"
        options={categories.map((category) => ({
          label: category.name,
          value: String(category.id),
        }))}
        value={String(form.category_id)}
        onChange={(value) => setForm((current) => ({ ...current, category_id: Number(value) }))}
      />

      <ChipSelect
        label="Unit"
        options={units.map((unit) => ({
          label: unit.symbol ? `${unit.name} (${unit.symbol})` : unit.name,
          value: String(unit.id),
        }))}
        value={String(form.unit_id)}
        onChange={(value) => setForm((current) => ({ ...current, unit_id: Number(value) }))}
      />

      <Input
        keyboardType="decimal-pad"
        label="Cost price"
        value={String(form.cost_price ?? '')}
        onChangeText={(cost_price) => setForm((current) => ({ ...current, cost_price }))}
      />

      <Input
        keyboardType="decimal-pad"
        label="Selling price"
        value={String(form.selling_price ?? '')}
        onChangeText={(selling_price) => setForm((current) => ({ ...current, selling_price }))}
      />

      <Input
        keyboardType="decimal-pad"
        label="Tax rate (%)"
        value={String(form.tax_rate ?? '')}
        onChangeText={(tax_rate) => setForm((current) => ({ ...current, tax_rate }))}
      />

      <Input
        keyboardType="number-pad"
        label="Reorder point"
        value={form.reorder_point === null ? '' : String(form.reorder_point)}
        onChangeText={(value) => setForm((current) => ({
          ...current,
          reorder_point: value.trim() === '' ? null : Number(value),
        }))}
      />

      <View style={styles.switchRow}>
        <Text style={styles.switchLabel}>Active</Text>
        <Switch
          value={form.is_active ?? true}
          onValueChange={(is_active) => setForm((current) => ({ ...current, is_active }))}
        />
      </View>

      <Button label={submitLabel} loading={isSubmitting} onPress={() => void handleSubmit()} />
    </FormScreen>
  );
}

const styles = StyleSheet.create({
  switchRow: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: theme.spacing.md,
    marginTop: theme.spacing.sm,
  },
  switchLabel: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    fontWeight: '600',
  },
});
