import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { theme } from '@/src/theme';
import type { Warehouse } from '@/src/api/types';

type WarehouseFilterProps = {
  warehouses: Warehouse[];
  value: number | null;
  onChange: (warehouseId: number | null) => void;
};

export function WarehouseFilter({ warehouses, value, onChange }: WarehouseFilterProps) {
  return (
    <View style={styles.container}>
      <Text style={styles.label}>Warehouse</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.row}>
        <Pressable
          onPress={() => onChange(null)}
          style={[styles.chip, value === null ? styles.chipSelected : null]}>
          <Text style={[styles.chipText, value === null ? styles.chipTextSelected : null]}>All</Text>
        </Pressable>
        {warehouses.map((warehouse) => (
          <Pressable
            key={warehouse.id}
            onPress={() => onChange(warehouse.id)}
            style={[styles.chip, value === warehouse.id ? styles.chipSelected : null]}>
            <Text
              style={[
                styles.chipText,
                value === warehouse.id ? styles.chipTextSelected : null,
              ]}>
              {warehouse.name}
            </Text>
          </Pressable>
        ))}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    marginBottom: theme.spacing.md,
    paddingHorizontal: theme.spacing.lg,
  },
  label: {
    ...theme.typography.label,
    color: theme.colors.textSecondary,
    marginBottom: theme.spacing.sm,
  },
  row: {
    gap: theme.spacing.sm,
  },
  chip: {
    backgroundColor: theme.colors.surface,
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
