import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

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
    marginBottom: 12,
    paddingHorizontal: 16,
  },
  label: {
    color: '#334155',
    fontSize: 13,
    fontWeight: '600',
    marginBottom: 8,
  },
  row: {
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
});
